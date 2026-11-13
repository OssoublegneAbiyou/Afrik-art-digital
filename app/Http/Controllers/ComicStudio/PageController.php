<?php

namespace App\Http\Controllers\ComicStudio;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessComicPage;
use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\ComicPage;
use App\Support\ComicImages;
use App\Support\ComicStudio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Planches d'un chapitre (appels AJAX de l'assistant) : envoi page par page, état de préparation,
 * ordre (glisser-déposer, flèches, tri naturel par nom), double page, description, remplacement, suppression.
 */
class PageController extends Controller
{
    public function store(Request $request, Comic $comic, ComicChapter $chapter): JsonResponse
    {
        $this->authorizeChapter($comic, $chapter);
        $file = $this->validatedFile($request);
        $user = $request->user();

        if ($chapter->pages()->count() >= ComicImages::maxPagesPerChapter()) {
            return $this->refuse($file->getClientOriginalName().' : '.ComicImages::maxPagesPerChapter().' planches au maximum par '.($comic->isOneShot() ? 'récit' : 'chapitre').'.');
        }

        if (ComicImages::usedBytes($user) + $file->getSize() > ComicImages::quotaBytes()) {
            return $this->refuse($file->getClientOriginalName().' : stockage des récits dessinés plein ('.ComicImages::formatBytes(ComicImages::quotaBytes()).'). Supprime des planches pour en ajouter.');
        }

        // L'ordre actuel suit-il encore les noms de fichiers ? (pas de réordonnancement manuel)
        $existing = $chapter->pages()->get()->mapWithKeys(fn (ComicPage $p) => [$p->id => (string) $p->nom_fichier])->all();
        $stillNatural = array_keys($existing) === array_keys(ComicImages::naturalSort($existing));

        $page = ComicPage::create([
            'comic_chapter_id' => $chapter->id,
            'ordre' => (int) $chapter->pages()->max('ordre') + 1,
            'nom_fichier' => mb_substr($file->getClientOriginalName(), 0, 255),
            'original' => ComicImages::storeOriginal($file, $comic, $chapter->id),
            'taille_octets' => (int) $file->getSize(),
            'statut_traitement' => ComicPage::PENDING,
        ]);

        // Envois successifs (ex. « 3, 4 » puis « 1, 2 ») : la nouvelle planche prend sa place par nom de fichier,
        // tant que l'artiste n'a pas réordonné à la main (sinon elle s'ajoute à la fin).
        if ($stillNatural) {
            $existing[$page->id] = (string) $page->nom_fichier;
            $this->applyOrder($chapter, array_keys(ComicImages::naturalSort($existing)));
        }

        $chapter->forceFill(['pages_count' => $chapter->pages()->count()])->save();
        $comic->touch();
        ProcessComicPage::dispatch($page->id);

        return response()->json([
            'page' => ComicStudio::pagePayload($page->refresh(), $comic, $chapter),
            'state' => ComicStudio::chapterPayload($comic, $chapter, $user),
        ], 201);
    }

    /** Petit « polling » de l'assistant pendant la préparation. */
    public function status(Request $request, Comic $comic, ComicChapter $chapter): JsonResponse
    {
        $this->authorizeChapter($comic, $chapter);

        // Secours si aucun « worker » de file ne tourne : une planche en attente depuis plus de 2 minutes
        // est préparée ici (une par appel).
        $stale = $chapter->pages()->where('statut_traitement', ComicPage::PENDING)
            ->where('updated_at', '<', now()->subMinutes(2))->first();

        if ($stale) {
            ComicImages::process($stale);
        }

        return response()->json(['state' => ComicStudio::chapterPayload($comic, $chapter, $request->user())]);
    }

    /** Nouvel ordre complet : pages[] = identifiants dans l'ordre de lecture. */
    public function reorder(Request $request, Comic $comic, ComicChapter $chapter): JsonResponse
    {
        $this->authorizeChapter($comic, $chapter);

        $ids = collect($request->validate(['pages' => ['required', 'array'], 'pages.*' => ['integer']])['pages'])
            ->map(fn ($id) => (int) $id);
        $existing = $chapter->pages()->pluck('id');

        if ($ids->count() !== $existing->count() || $ids->unique()->count() !== $ids->count() || $ids->diff($existing)->isNotEmpty()) {
            return $this->refuse('L’ordre envoyé ne correspond pas aux planches du chapitre : recharge la page.');
        }

        $this->applyOrder($chapter, $ids->all());

        return response()->json(['state' => ComicStudio::chapterPayload($comic, $chapter, $request->user())]);
    }

    /** Tri naturel par nom de fichier (1, 2, … 10, 11). */
    public function sort(Request $request, Comic $comic, ComicChapter $chapter): JsonResponse
    {
        $this->authorizeChapter($comic, $chapter);

        $names = $chapter->pages()->get()->mapWithKeys(fn (ComicPage $page) => [$page->id => (string) $page->nom_fichier])->all();
        $this->applyOrder($chapter, array_keys(ComicImages::naturalSort($names)));

        return response()->json(['state' => ComicStudio::chapterPayload($comic, $chapter, $request->user())]);
    }

    public function update(Request $request, Comic $comic, ComicChapter $chapter, ComicPage $page): JsonResponse
    {
        $this->authorizePage($comic, $chapter, $page);

        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'double_page' => ['nullable', 'boolean'],
        ], ['alt.max' => 'La description ne doit pas dépasser 255 caractères.']);

        if ($request->has('alt')) {
            $page->alt = filled($data['alt'] ?? null) ? trim($data['alt']) : null;
        }

        if ($request->has('double_page')) {
            $page->double_page = (bool) $data['double_page'];
        }

        $page->save();

        return response()->json(['page' => ComicStudio::pagePayload($page, $comic, $chapter)]);
    }

    public function replace(Request $request, Comic $comic, ComicChapter $chapter, ComicPage $page): JsonResponse
    {
        $this->authorizePage($comic, $chapter, $page);
        $file = $this->validatedFile($request);
        $user = $request->user();

        if (ComicImages::usedBytes($user) - (int) $page->taille_octets + $file->getSize() > ComicImages::quotaBytes()) {
            return $this->refuse('Stockage des récits dessinés plein : supprime des planches pour remplacer celle-ci.');
        }

        ComicImages::deleteFiles($page);

        $page->forceFill([
            'nom_fichier' => mb_substr($file->getClientOriginalName(), 0, 255),
            'original' => ComicImages::storeOriginal($file, $comic, $chapter->id),
            'lecture' => null,
            'miniature' => null,
            'tranches' => null,
            'largeur' => null,
            'hauteur' => null,
            'taille_octets' => (int) $file->getSize(),
            'statut_traitement' => ComicPage::PENDING,
            'erreur' => null,
        ])->save();

        ProcessComicPage::dispatch($page->id);

        return response()->json([
            'page' => ComicStudio::pagePayload($page->refresh(), $comic, $chapter),
            'state' => ComicStudio::chapterPayload($comic, $chapter, $user),
        ]);
    }

    public function destroy(Request $request, Comic $comic, ComicChapter $chapter, ComicPage $page): JsonResponse
    {
        $this->authorizePage($comic, $chapter, $page);

        ComicImages::deleteFiles($page);
        $page->delete();
        $this->applyOrder($chapter, $chapter->pages()->pluck('id')->all());
        $chapter->forceFill(['pages_count' => $chapter->pages()->count()])->save();

        return response()->json(['state' => ComicStudio::chapterPayload($comic, $chapter, $request->user())]);
    }

    // ---------- Aides ----------

    private function validatedFile(Request $request): UploadedFile
    {
        $request->validate([
            // « mimes » s'appuie sur le contenu réel du fichier (finfo), pas sur son extension.
            'page' => ['required', 'file', 'max:'.(ComicImages::maxPageMb() * 1024), 'mimes:'.implode(',', ComicImages::extensions())],
        ], [
            'page.required' => 'Choisis une image.',
            'page.file' => 'Le fichier n’a pas pu être envoyé.',
            'page.uploaded' => 'Le fichier n’a pas pu être envoyé (trop lourd pour le serveur ?).',
            'page.max' => 'Chaque planche doit peser '.ComicImages::maxPageMb().' Mo au plus.',
            'page.mimes' => 'Format non accepté : '.ComicImages::formatsLabel().' uniquement.',
        ]);

        return $request->file('page');
    }

    private function applyOrder(ComicChapter $chapter, array $ids): void
    {
        DB::transaction(function () use ($chapter, $ids) {
            foreach (array_values($ids) as $index => $id) {
                ComicPage::where('comic_chapter_id', $chapter->id)->whereKey($id)->update(['ordre' => $index + 1]);
            }
        });
    }

    private function refuse(string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => ['page' => [$message]]], 422);
    }

    private function authorizeChapter(Comic $comic, ComicChapter $chapter): void
    {
        Gate::authorize('manage', $comic);
        abort_unless($chapter->comic_id === $comic->id, 404);
    }

    private function authorizePage(Comic $comic, ComicChapter $chapter, ComicPage $page): void
    {
        $this->authorizeChapter($comic, $chapter);
        abort_unless($page->comic_chapter_id === $chapter->id, 404);
    }
}
