<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Illustration;
use App\Models\IllustrationType;
use App\Notifications\MemberActivity;
use App\Support\AdminNotifier;
use App\Support\ImageThumbnail;
use App\Support\PlatformRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IllustrationController extends Controller
{
    public const RIGHTS_MESSAGE = 'Coche la case pour certifier que tu es l’auteur de l’œuvre et que tu as le droit de la publier.';

    /**
     * Publication d'une ou plusieurs images en une fois (regles.images_par_envoi, 10 par défaut).
     * Avec une seule image, le titre saisi est utilisé ; sinon chaque titre vient du nom du fichier (modifiable ensuite).
     */
    public function store(Request $request): RedirectResponse
    {
        $artist = $this->artistFor($request);
        $maxBatch = PlatformRules::imagesPerUpload();
        $maxMb = PlatformRules::maxImageMb();
        $quota = PlatformRules::maxWorks();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['required', IllustrationType::rule()],
            'description' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'string', 'max:300'],
            // « images[] » (envoi multiple) ou « image » (ancien formulaire, une seule image).
            'images' => ['required_without:image', 'array', 'min:1', 'max:'.$maxBatch],
            'images.*' => ['image', 'max:'.PlatformRules::maxImageKb()],
            'image' => ['nullable', 'image', 'max:'.PlatformRules::maxImageKb()],
            'rights' => ['accepted'],
            ...$this->publicationRules(),
        ], [
            'rights.accepted' => self::RIGHTS_MESSAGE,
            'type.required' => 'Choisis le type de l’illustration ('.$this->typeChoices().').',
            'type.in' => 'Choisis un type proposé dans la liste ('.$this->typeChoices().').',
            'images.required_without' => 'Choisis au moins une image.',
            'images.max' => 'Tu peux envoyer jusqu’à '.$maxBatch.' images à la fois.',
            'images.*.image' => 'Chaque fichier doit être une image (JPG, PNG ou WEBP).',
            'images.*.max' => 'Chaque image doit faire '.$maxMb.' Mo au maximum.',
            'image.image' => 'Le fichier doit être une image (JPG, PNG ou WEBP).',
            'image.max' => 'L’image doit faire '.$maxMb.' Mo au maximum.',
            'scheduled_at.required_if' => 'Choisis la date de publication.',
            'scheduled_at.after' => 'La date de publication doit être dans le futur.',
        ]);

        /** @var array<int, UploadedFile> $files */
        $files = array_values($data['images'] ?? array_filter([$data['image'] ?? null]));
        $remaining = $quota - $artist->allIllustrations()->count();

        if (count($files) > $remaining) {
            return back()->withInput()->withErrors([
                'quota' => $remaining > 0
                    ? 'Il te reste '.$remaining.' place'.($remaining >= 2 ? 's' : '').' sur '.$quota.' : retire des images de ton envoi ou supprime des œuvres.'
                    : 'Quota de '.$quota.' illustrations atteint. Supprime une œuvre pour en ajouter une nouvelle.',
            ]);
        }

        $incomingSize = collect($files)->sum(fn (UploadedFile $file) => $file->getSize());

        if (($request->user()->storageUsedBytes() + $incomingSize) > $request->user()->storageLimitBytes()) {
            return back()->withInput()->withErrors([
                'storage' => 'Espace de stockage de '.PlatformRules::storageLabel().' dépassé. Supprime des fichiers avant d’ajouter de nouvelles illustrations.',
            ]);
        }

        $publishedAt = Illustration::publicationDateFor($data['status'], $data['scheduled_at'] ?? null);
        $moderation = Illustration::moderationStatusFor($request->user());
        $single = count($files) === 1;

        foreach ($files as $file) {
            Illustration::create([
                'artist_id' => $artist->id,
                'title' => $single && filled($data['title'] ?? null) ? $data['title'] : $this->titleFromFile($file),
                'type' => $data['type'],
                'description' => $single ? ($data['description'] ?? null) : null,
                'tags' => Illustration::normalizeTags($data['tags'] ?? null) ?: null,
                'image_path' => $file->store('illustrations', 'public'),
                'file_size_bytes' => $file->getSize(),
                'published_at' => $publishedAt,
                'moderation_status' => $moderation,
                'rights_certified_at' => now(),
            ]);
        }

        $count = count($files);
        $pending = $moderation === Illustration::MODERATION_PENDING;

        if ($pending && $data['status'] !== Illustration::STATUS_DRAFT) {
            AdminNotifier::send(MemberActivity::toReview($request->user(), $count, 'illustration'));
        }

        $what = $count >= 2 ? $count.' illustrations ajoutées' : 'Illustration ajoutée';
        $how = match (true) {
            $data['status'] === Illustration::STATUS_DRAFT => ' en brouillon.',
            $pending => ' : elle'.($count >= 2 ? 's seront visibles' : ' sera visible').' après validation par l’équipe'
                .($data['status'] === Illustration::STATUS_SCHEDULED ? ', au plus tôt le '.$publishedAt->locale('fr')->translatedFormat('j F à H\hi') : '').'.',
            $data['status'] === Illustration::STATUS_SCHEDULED => ', publication programmée le '.$publishedAt->locale('fr')->translatedFormat('j F à H\hi').'.',
            default => ' et publiée'.($count >= 2 ? 's' : '').'.',
        };

        return back()->with('success', $what.$how.($count >= 2 ? ' Les titres viennent des noms de fichiers : tu peux les modifier.' : ''));
    }

    public function edit(Request $request, Illustration $illustration): View
    {
        abort_unless($illustration->isOwnedBy($request->user()), 403);

        return view('dashboard.illustration-edit', [
            'illustration' => $illustration->loadCount(['likes', 'favoritedBy']),
            'types' => IllustrationType::options($illustration->type),
            'maxImageMb' => PlatformRules::maxImageMb(),
        ]);
    }

    /**
     * Mise à jour : complète depuis la page de modification, ou seulement le type depuis la grille de l'espace artiste.
     */
    public function update(Request $request, Illustration $illustration): RedirectResponse
    {
        abort_unless($illustration->isOwnedBy($request->user()), 403, 'Non autorisé.');

        if (! $request->boolean('full')) {
            $data = $request->validate([
                'type' => ['required', IllustrationType::rule($illustration->type)],
            ], [
                'type.in' => 'Choisis un type proposé dans la liste.',
            ]);
            $illustration->update($data);

            return back()->with('success', '« '.$illustration->title.' » est classée en '.mb_strtolower($illustration->typeLabel()).'.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', IllustrationType::rule($illustration->type)],
            'description' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'string', 'max:300'],
            'image' => ['nullable', 'image', 'max:'.PlatformRules::maxImageKb()],
            // Nouvelle image : nouvelle certification des droits.
            'rights' => $request->hasFile('image') ? ['accepted'] : ['nullable'],
            ...$this->publicationRules($illustration),
        ], [
            'rights.accepted' => self::RIGHTS_MESSAGE,
            'type.in' => 'Choisis un type proposé dans la liste.',
            'image.image' => 'Le fichier doit être une image (JPG, PNG ou WEBP).',
            'image.max' => 'L’image doit faire '.PlatformRules::maxImageMb().' Mo au maximum.',
            'scheduled_at.required_if' => 'Choisis la date de publication.',
            'scheduled_at.after' => 'La date de publication doit être dans le futur.',
        ]);

        $attributes = [
            'title' => $data['title'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'tags' => Illustration::normalizeTags($data['tags'] ?? null) ?: null,
            'published_at' => Illustration::publicationDateFor($data['status'], $data['scheduled_at'] ?? null, $illustration->published_at),
        ];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extra = $file->getSize() - (int) $illustration->file_size_bytes;

            if ($extra > 0 && ($request->user()->storageUsedBytes() + $extra) > $request->user()->storageLimitBytes()) {
                return back()->withInput()->withErrors(['image' => 'Espace de stockage de '.PlatformRules::storageLabel().' dépassé : impossible de remplacer l’image.']);
            }

            Storage::disk('public')->delete($illustration->image_path);
            ImageThumbnail::delete($illustration->image_path);
            $attributes['image_path'] = $file->store('illustrations', 'public');
            $attributes['file_size_bytes'] = $file->getSize();
            $attributes['rights_certified_at'] = now();
            ImageThumbnail::generate($attributes['image_path']);
        }

        // Nouvelle image, ou œuvre refusée que l'auteur corrige : retour dans la file de validation.
        $resubmitted = ($request->hasFile('image') || $illustration->isRejected())
            && Illustration::moderationStatusFor($request->user()) === Illustration::MODERATION_PENDING;

        if ($resubmitted) {
            $attributes['moderation_status'] = Illustration::MODERATION_PENDING;
            $attributes['moderation_note'] = null;
        }

        $wasDraft = $illustration->isDraft();
        $illustration->update($attributes);

        // À prévenir : une resoumission, ou un brouillon en attente que l'auteur publie enfin.
        $leftDraft = $wasDraft && ! $illustration->isDraft() && $illustration->isPendingReview();

        if (($resubmitted || $leftDraft) && ! $illustration->isDraft()) {
            AdminNotifier::send(MemberActivity::toReview($request->user(), 1, 'illustration'));
        }

        return redirect()->route('illustrations.edit', $illustration)->with('success', $resubmitted
            ? 'Modifications enregistrées : l’œuvre sera de nouveau visible après validation par l’équipe.'
            : 'Modifications enregistrées.');
    }

    public function destroy(Request $request, Illustration $illustration): RedirectResponse
    {
        abort_unless($request->user()?->isStaff() || $illustration->isOwnedBy($request->user()), 403, 'Non autorisé.');

        Storage::disk('public')->delete($illustration->image_path);
        $illustration->delete();

        return $request->boolean('from_edit')
            ? redirect()->route('dashboard')->with('success', 'Illustration supprimée avec succès.')
            : back()->with('success', 'Illustration supprimée avec succès.');
    }

    private function artistFor(Request $request): Artist
    {
        abort_unless($request->user()->isArtist(), 403);

        return $request->user()->artist()->firstOrCreate([]);
    }

    private function publicationRules(?Illustration $current = null): array
    {
        // Sans choix explicite : publication immédiate.
        if (blank(request()->input('status'))) {
            request()->merge(['status' => Illustration::STATUS_PUBLISHED]);
        }

        return [
            'status' => ['required', Rule::in([Illustration::STATUS_PUBLISHED, Illustration::STATUS_DRAFT, Illustration::STATUS_SCHEDULED])],
            'scheduled_at' => ['nullable', 'required_if:status,'.Illustration::STATUS_SCHEDULED, 'date', 'after:now'],
        ];
    }

    /**
     * « croquis, couleur, couverture ou autre » (types proposés à l'envoi).
     */
    private function typeChoices(): string
    {
        $labels = array_map('mb_strtolower', array_values(IllustrationType::options()));
        $last = array_pop($labels);

        return $labels ? implode(', ', $labels).' ou '.$last : (string) $last;
    }

    /**
     * « mon-dessin_final-v2.png » → « Mon dessin final v2 ».
     */
    private function titleFromFile(UploadedFile $file): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = trim(preg_replace('/[\s_\-.]+/u', ' ', $name));

        return Str::limit(Str::ucfirst($name !== '' ? $name : 'Sans titre'), 255, '');
    }
}
