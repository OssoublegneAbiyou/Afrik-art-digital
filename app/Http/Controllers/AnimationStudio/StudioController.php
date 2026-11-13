<?php

namespace App\Http\Controllers\AnimationStudio;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAnimation;
use App\Models\Animation;
use App\Models\Genre;
use App\Notifications\MemberActivity;
use App\Support\AdminNotifier;
use App\Support\AnimationMedia;
use App\Support\AnimationStudio;
use App\Support\Ffmpeg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * « Mes animations » : liste, envoi, fiche de gestion (conversion, poster, publication, statistiques),
 * modification et suppression. La conversion se fait dans le job ProcessAnimation (file d'attente).
 */
class StudioController extends Controller
{
    public const RIGHTS_MESSAGE = 'Coche la case pour certifier que tu es l’auteur de cette animation.';

    public function index(Request $request): View
    {
        return view('animations.studio.index', [
            'animations' => AnimationStudio::animationsFor($request->user()),
            'storage' => AnimationMedia::storage($request->user()),
            'ffmpegAvailable' => Ffmpeg::available(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('animations.studio.form', [
            'animation' => null,
            'storage' => AnimationMedia::storage($request->user()),
            'ffmpegAvailable' => Ffmpeg::available(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $user = $request->user();
        $file = $request->file('fichier');

        $this->ensureQuota($file, $user, 0);
        $this->ensureCanConvert();

        $animation = new Animation($this->attributes($data));
        $animation->user_id = $user->id;
        $animation->source = AnimationMedia::storeSource($file, $user);
        $animation->source_nom = mb_substr($file->getClientOriginalName(), 0, 255);
        $animation->taille_octets = (int) $file->getSize();
        $animation->statut_traitement = Animation::QUEUED;
        // Brouillon jusqu'à la publication (une fois la conversion terminée).
        $animation->published_at = null;
        $animation->moderation_status = Animation::MODERATION_APPROVED;
        $animation->rights_certified_at = now();
        $animation->save();

        ProcessAnimation::dispatch($animation->id);

        return redirect()->route('animations.studio.manage', $animation)
            ->with('success', 'Vidéo envoyée : la conversion se fait en arrière-plan. Tu peux rester sur cette page ou revenir plus tard.');
    }

    /** Fiche de gestion : état de la conversion, aperçu, poster, publication, statistiques. */
    public function manage(Request $request, Animation $animation): View
    {
        Gate::authorize('manage', $animation);

        return view('animations.studio.manage', [
            'animation' => $animation,
            'status' => AnimationStudio::statusFor($animation),
            'state' => AnimationStudio::payload($animation),
            'stats' => AnimationStudio::stats($animation),
            'publicUrl' => AnimationStudio::publicUrl($animation),
            'needsReview' => Animation::moderationStatusFor($request->user()) === Animation::MODERATION_PENDING,
            'ffmpegAvailable' => Ffmpeg::available(),
        ]);
    }

    public function edit(Request $request, Animation $animation): View
    {
        Gate::authorize('manage', $animation);

        return view('animations.studio.form', [
            'animation' => $animation,
            'storage' => AnimationMedia::storage($request->user()),
            'ffmpegAvailable' => Ffmpeg::available(),
        ]);
    }

    public function update(Request $request, Animation $animation): RedirectResponse
    {
        Gate::authorize('manage', $animation);

        $data = $this->validated($request, $animation);
        $user = $request->user();
        $file = $request->file('fichier');
        $modeChanged = $data['mode'] !== $animation->mode;

        // Nouvelle vidéo, ou changement boucle ↔ clip (piste son à retirer / garder) : nouvelle conversion.
        if ($file || $modeChanged) {
            $this->ensureCanConvert();
        }

        if ($file) {
            $this->ensureQuota($file, $user, (int) $animation->taille_octets);
        }

        $animation->fill($this->attributes($data));

        if ($animation->isDirty('titre') && ! $animation->isVisible() && ! $animation->isPublished()) {
            // Tant que l'animation n'a jamais été en ligne, son adresse suit le titre.
            $animation->slug = Animation::uniqueSlug($animation->titre, $animation->id);
        }

        $reconvert = false;

        if ($file) {
            Storage::disk(Animation::PRIVATE_DISK)->delete($animation->source);
            $animation->source = AnimationMedia::storeSource($file, $user);
            $animation->source_nom = mb_substr($file->getClientOriginalName(), 0, 255);
            $animation->poster_seconde = null;
            $animation->rights_certified_at = now();
            $reconvert = true;

            // Nouvelle vidéo sur une animation déjà soumise : l'équipe la revoit (si la validation est active).
            if ($animation->published_at && Animation::moderationStatusFor($user) === Animation::MODERATION_PENDING) {
                $animation->moderation_status = Animation::MODERATION_PENDING;
                $animation->moderated_at = null;
                $animation->moderation_note = null;
            }
        } elseif ($modeChanged) {
            $reconvert = true;
        }

        if ($reconvert) {
            $animation->statut_traitement = Animation::QUEUED;
            $animation->erreur = null;
        }

        $resubmitted = $animation->isDirty('moderation_status') && $animation->isPendingReview();
        $animation->save();

        if ($reconvert) {
            ProcessAnimation::dispatch($animation->id);
        }

        if ($resubmitted) {
            AdminNotifier::send(MemberActivity::toReview($user, 1, 'animation'), $user);
        }

        return redirect()->route('animations.studio.manage', $animation)->with('success', match (true) {
            (bool) $file => 'Nouvelle vidéo envoyée : la conversion se fait en arrière-plan.'.($resubmitted ? ' L’équipe la validera ensuite.' : ''),
            $reconvert => 'Fiche enregistrée. La vidéo est convertie à nouveau ('.($animation->isLoop() ? 'boucle sans son' : 'clip avec son').').',
            default => 'Fiche enregistrée.',
        });
    }

    public function destroy(Animation $animation): RedirectResponse
    {
        Gate::authorize('manage', $animation);

        $title = $animation->titre;
        AnimationStudio::delete($animation);

        return redirect()->route('animations.studio.index')->with('success', '« '.$title.' » a été supprimée, avec ses fichiers.');
    }

    // ---------- Aides ----------

    private function validated(Request $request, ?Animation $animation): array
    {
        $creating = $animation === null;

        return $request->validate([
            'titre' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'genre' => ['nullable', Genre::rule($animation?->genre)],
            'tags' => ['nullable', 'string', 'max:300'],
            'mode' => ['required', Rule::in([Animation::LOOP, Animation::CLIP])],
            'contenu_sensible' => ['nullable', 'boolean'],
            'rights' => $creating ? ['accepted'] : ['nullable'],
            'fichier' => [
                $creating ? 'required' : 'nullable', 'file',
                'extensions:'.implode(',', AnimationMedia::extensions()),
                'max:'.(AnimationMedia::maxFileMb() * 1024),
            ],
        ], [
            'titre.required' => 'Donne un titre à ton animation.',
            'titre.max' => 'Le titre ne doit pas dépasser 120 caractères.',
            'description.max' => 'La description ne doit pas dépasser 1 000 caractères.',
            'genre.in' => 'Choisis un genre de la liste.',
            'mode.required' => 'Choisis : boucle ou clip.',
            'mode.in' => 'Choisis : boucle ou clip.',
            'rights.accepted' => self::RIGHTS_MESSAGE,
            'fichier.required' => 'Choisis la vidéo à envoyer.',
            'fichier.file' => 'Le fichier n’a pas pu être envoyé. Réessaie.',
            'fichier.uploaded' => 'Le fichier n’a pas pu être envoyé : il dépasse peut-être la taille permise par le serveur ('.AnimationMedia::maxFileMb().' Mo au plus).',
            'fichier.extensions' => 'Format non accepté : envoie une vidéo '.AnimationMedia::formatsLabel().'.',
            'fichier.max' => 'La vidéo ne doit pas dépasser '.AnimationMedia::maxFileMb().' Mo.',
        ]);
    }

    private function attributes(array $data): array
    {
        return [
            'titre' => trim($data['titre']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'genre' => $data['genre'] ?? null,
            'tags' => Animation::normalizeTags($data['tags'] ?? null),
            'mode' => $data['mode'],
            'contenu_sensible' => (bool) ($data['contenu_sensible'] ?? false),
        ];
    }

    /** Sans ffmpeg sur le serveur, l'envoi est suspendu (message clair, rien n'est enregistré). */
    private function ensureCanConvert(): void
    {
        if (! Ffmpeg::available()) {
            throw ValidationException::withMessages(['fichier' => Ffmpeg::UNAVAILABLE_MESSAGE]);
        }
    }

    private function ensureQuota(UploadedFile $file, $user, int $replacedBytes): void
    {
        $available = AnimationMedia::quotaBytes() - AnimationMedia::usedBytes($user) + $replacedBytes;

        if ((int) $file->getSize() > $available) {
            throw ValidationException::withMessages([
                'fichier' => 'Stockage des animations plein ('.AnimationMedia::storage($user)['usedLabel'].' sur '
                    .AnimationMedia::storage($user)['limitLabel'].') : supprime des animations avant d’en envoyer une nouvelle.',
            ]);
        }
    }
}
