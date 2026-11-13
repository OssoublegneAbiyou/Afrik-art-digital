<?php

namespace App\Http\Controllers\ComicStudio;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\Genre;
use App\Support\ComicImages;
use App\Support\ComicStudio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * « Mes récits dessinés » : liste, fiche du récit (étape 1 de l'assistant), page « Gérer », suppression.
 */
class StudioController extends Controller
{
    public const RIGHTS_MESSAGE = 'Coche la case pour certifier que tu es l’auteur de ces planches.';

    public function index(Request $request): View
    {
        return view('comics.studio.index', [
            'comics' => ComicStudio::comicsFor($request->user()),
            'storage' => ComicStudio::storage($request->user()),
        ]);
    }

    public function create(): View
    {
        return view('comics.studio.form', ['comic' => null, 'chapter' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $user = $request->user();

        $comic = DB::transaction(function () use ($data, $user) {
            $comic = new Comic($this->attributes($data, null));
            $comic->user_id = $user->id;
            // Brouillon jusqu'à l'étape « Publication ».
            $comic->published_at = null;
            $comic->moderation_status = Comic::MODERATION_APPROVED;
            $comic->rights_certified_at = now();
            $comic->save();

            $chapter = new ComicChapter([
                'comic_id' => $comic->id,
                'numero' => 1,
                'titre' => $comic->isOneShot() ? null : ($data['chapitre_titre'] ?? null),
            ]);
            $chapter->published_at = null;
            $chapter->rights_certified_at = now();
            $chapter->save();

            return $comic;
        });

        $chapter = $comic->allChapters()->first();

        try {
            $this->storeCover($request, $comic);
        } catch (ValidationException $exception) {
            // Le brouillon existe déjà : on rouvre sa fiche (pas de doublon si l'auteur renvoie le formulaire).
            return redirect()->route('comics.studio.edit', [$comic, 'chapitre' => $chapter->id])
                ->withErrors($exception->errors());
        }

        return redirect()->route('comics.studio.chapters.pages', [$comic, $chapter])
            ->with('success', 'Fiche enregistrée. Ajoute maintenant tes planches.');
    }

    /** Étape 1 pour un récit existant (retour dans l'assistant ou modification de la fiche). */
    public function edit(Request $request, Comic $comic): View
    {
        Gate::authorize('manage', $comic);

        $chapter = $request->filled('chapitre')
            ? $comic->allChapters()->whereKey($request->integer('chapitre'))->first()
            : null;

        return view('comics.studio.form', ['comic' => $comic, 'chapter' => $chapter]);
    }

    public function update(Request $request, Comic $comic): RedirectResponse
    {
        Gate::authorize('manage', $comic);

        $data = $this->validated($request, $comic);
        $comic->fill($this->attributes($data, $comic));

        if ($comic->isDirty('titre') && ! $comic->isVisible()) {
            // Tant que le récit n'a jamais été en ligne, son adresse suit le titre.
            $comic->slug = Comic::uniqueSlug($comic->titre, $comic->id);
        }

        $comic->save();
        $this->storeCover($request, $comic);

        $next = $request->filled('chapitre')
            ? $comic->allChapters()->whereKey($request->integer('chapitre'))->first()
            : null;

        return $next
            ? redirect()->route('comics.studio.chapters.pages', [$comic, $next])->with('success', 'Fiche enregistrée.')
            : redirect()->route('comics.studio.manage', $comic)->with('success', 'Fiche du récit enregistrée.');
    }

    public function manage(Comic $comic): View
    {
        Gate::authorize('manage', $comic);

        $chapters = $comic->allChapters()->withCount('pages')->get();

        return view('comics.studio.manage', [
            'comic' => $comic,
            'chapters' => $chapters,
            'stats' => ComicStudio::stats($comic),
            'status' => ComicStudio::statusFor($comic->setRelation('allChapters', $chapters)),
            'nextNumber' => ComicStudio::nextNumber($comic),
        ]);
    }

    public function destroy(Request $request, Comic $comic): RedirectResponse
    {
        Gate::authorize('manage', $comic);

        $title = $comic->titre;
        ComicStudio::deleteComic($comic);

        return redirect()->route('comics.studio.index')->with('success', '« '.$title.' » a été supprimé, avec toutes ses planches.');
    }

    // ---------- Aides ----------

    private function validated(Request $request, ?Comic $comic): array
    {
        $creating = $comic === null;
        // Le type ne change plus une fois la série commencée (plusieurs chapitres).
        $typeLocked = $comic && $comic->allChapters()->count() > 1;

        return $request->validate([
            'type' => [$typeLocked ? 'nullable' : 'required', Rule::in(array_keys(Comic::TYPES))],
            'titre' => ['required', 'string', 'max:120'],
            'resume' => ['nullable', 'string', 'max:600'],
            'couverture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(ComicImages::maxPageMb() * 1024)],
            'genre' => ['nullable', Genre::rule($comic?->genre)],
            'tags' => ['nullable', 'string', 'max:300'],
            'format_lecture' => ['required', Rule::in(array_keys(Comic::FORMATS))],
            'sens_lecture' => ['required', Rule::in(array_keys(Comic::DIRECTIONS))],
            'statut_serie' => ['nullable', Rule::in(array_keys(Comic::SERIES_STATUSES))],
            'contenu_sensible' => ['nullable', 'boolean'],
            'chapitre_titre' => ['nullable', 'string', 'max:120'],
            'rights' => $creating ? ['accepted'] : ['nullable'],
        ], [
            'type.required' => 'Choisis : one-shot ou série.',
            'titre.required' => 'Donne un titre à ton récit.',
            'titre.max' => 'Le titre ne doit pas dépasser 120 caractères.',
            'resume.max' => 'Le résumé ne doit pas dépasser 600 caractères.',
            'couverture.image' => 'La couverture doit être une image.',
            'couverture.mimes' => 'La couverture doit être au format JPG, PNG ou WEBP.',
            'couverture.max' => 'La couverture ne doit pas dépasser '.ComicImages::maxPageMb().' Mo.',
            'genre.in' => 'Choisis un genre de la liste.',
            'rights.accepted' => self::RIGHTS_MESSAGE,
        ]);
    }

    private function attributes(array $data, ?Comic $comic): array
    {
        $type = $data['type'] ?? $comic?->type ?? Comic::ONE_SHOT;

        return [
            'type' => $comic && $comic->allChapters()->count() > 1 ? $comic->type : $type,
            'titre' => trim($data['titre']),
            'resume' => isset($data['resume']) ? trim($data['resume']) : null,
            'genre' => $data['genre'] ?? null,
            'tags' => Comic::normalizeTags($data['tags'] ?? null),
            'format_lecture' => $data['format_lecture'],
            'sens_lecture' => $data['sens_lecture'],
            'statut_serie' => $type === Comic::SERIES ? ($data['statut_serie'] ?? 'en_cours') : 'terminee',
            'contenu_sensible' => (bool) ($data['contenu_sensible'] ?? false),
        ];
    }

    private function storeCover(Request $request, Comic $comic): void
    {
        if (! $request->hasFile('couverture')) {
            return;
        }

        $file = $request->file('couverture');
        $available = ComicImages::quotaBytes() - ComicImages::usedBytes($request->user()) + (int) $comic->couverture_octets;

        if ($file->getSize() > $available) {
            throw ValidationException::withMessages([
                'couverture' => 'Stockage des récits dessinés plein : supprime des planches avant d’ajouter cette couverture.',
            ]);
        }

        $stored = ComicImages::storeCover($file, $comic);

        if (! $stored) {
            throw ValidationException::withMessages(['couverture' => 'Impossible de lire cette image de couverture.']);
        }

        $old = $comic->couverture;
        $comic->forceFill(['couverture' => $stored[0], 'couverture_octets' => $stored[1]])->save();

        if ($old && $old !== $stored[0]) {
            Storage::disk('public')->delete($old);
        }
    }
}
