<?php

namespace App\Http\Controllers\AnimationStudio;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAnimation;
use App\Models\Animation;
use App\Notifications\MemberActivity;
use App\Support\AdminNotifier;
use App\Support\AnimationMedia;
use App\Support\AnimationStudio;
use App\Support\ComicStudio;
use App\Support\Ffmpeg;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Suivi de la conversion (JSON), relance, choix du poster et publication (publier / programmer / brouillon).
 */
class PublicationController extends Controller
{
    /** État de la conversion, interrogé régulièrement par la fiche de gestion. */
    public function status(Animation $animation): JsonResponse
    {
        Gate::authorize('manage', $animation);

        // Secours si aucun « worker » de file ne tourne (hébergement mutualisé) : une animation en file depuis
        // plus de 2 minutes est convertie ici, une seule fois (réservation atomique du statut).
        $claimed = $animation->statut_traitement === Animation::QUEUED
            && $animation->updated_at?->lt(now()->subMinutes(2))
            && Animation::withUnpublished()->whereKey($animation->id)
                ->where('statut_traitement', Animation::QUEUED)
                ->update(['statut_traitement' => Animation::PROCESSING, 'updated_at' => now()]) === 1;

        if ($claimed) {
            @set_time_limit(Ffmpeg::timeout() + 60);
            AnimationMedia::process($animation->refresh());
        }

        return response()->json(AnimationStudio::payload($animation->refresh()));
    }

    /** Relance une conversion en échec (ffmpeg revenu, erreur passagère…). */
    public function retry(Animation $animation): RedirectResponse
    {
        Gate::authorize('manage', $animation);
        $back = redirect()->route('animations.studio.manage', $animation);

        if ($animation->statut_traitement !== Animation::FAILED) {
            return $back;
        }

        if (! Ffmpeg::available()) {
            return $back->with('error', Ffmpeg::UNAVAILABLE_MESSAGE);
        }

        $animation->forceFill(['statut_traitement' => Animation::QUEUED, 'erreur' => null])->save();
        ProcessAnimation::dispatch($animation->id);

        return $back->with('success', 'Conversion relancée.');
    }

    /** Poster : image extraite à l'instant choisi (en secondes). */
    public function poster(Request $request, Animation $animation): RedirectResponse
    {
        Gate::authorize('manage', $animation);
        $back = redirect()->route('animations.studio.manage', $animation);

        if (! $animation->isReady()) {
            return $back->with('error', 'Attends la fin de la conversion pour choisir le poster.');
        }

        $max = max(0, (float) $animation->duree_s);
        $data = $request->validate([
            'poster_seconde' => ['required', 'numeric', 'min:0', 'max:'.$max],
        ], [
            'poster_seconde.required' => 'Choisis l’instant du poster.',
            'poster_seconde.numeric' => 'L’instant du poster doit être un nombre de secondes.',
            'poster_seconde.min' => 'L’instant du poster doit être positif.',
            'poster_seconde.max' => 'L’instant du poster doit être dans la vidéo ('.number_format($max, 1, ',', ' ').' s au plus).',
        ]);

        if (! Ffmpeg::available()) {
            return $back->with('error', Ffmpeg::UNAVAILABLE_MESSAGE);
        }

        if (! AnimationMedia::regeneratePoster($animation, (float) $data['poster_seconde'])) {
            return $back->with('error', 'Impossible d’extraire une image à cet instant. Essaie un autre moment.');
        }

        return $back->with('success', 'Poster mis à jour (image à '.number_format((float) $animation->poster_seconde, 1, ',', ' ').' s).');
    }

    public function publish(Request $request, Animation $animation): RedirectResponse
    {
        Gate::authorize('manage', $animation);

        $data = $request->validate([
            'action' => ['required', Rule::in([Animation::STATUS_PUBLISHED, Animation::STATUS_SCHEDULED, Animation::STATUS_DRAFT])],
            'scheduled_at' => ['nullable', 'required_if:action,'.Animation::STATUS_SCHEDULED, 'date_format:Y-m-d\TH:i'],
        ], [
            'action.required' => 'Choisis : publier, programmer ou garder en brouillon.',
            'scheduled_at.required_if' => 'Indique la date et l’heure de sortie.',
            'scheduled_at.date_format' => 'Date de sortie invalide.',
        ]);

        $user = $request->user();
        $back = redirect()->route('animations.studio.manage', $animation);

        if ($data['action'] === Animation::STATUS_DRAFT) {
            $animation->update(['published_at' => null]);

            return $back->with('success', 'Brouillon enregistré : ton animation n’est visible que par toi.');
        }

        if (! $animation->isReady()) {
            return $back->withErrors(['publication' => $animation->statut_traitement === Animation::FAILED
                ? 'La conversion a échoué : relance-la ou envoie une autre vidéo avant de publier.'
                : 'La vidéo est encore en conversion : patiente un instant avant de publier.']);
        }

        $scheduledAt = null;

        if ($data['action'] === Animation::STATUS_SCHEDULED) {
            $scheduledAt = ComicStudio::parseLocal($data['scheduled_at']);

            if (! $scheduledAt || $scheduledAt->lte(now())) {
                return $back->withErrors(['scheduled_at' => 'Choisis une date et une heure à venir.'])->withInput();
            }
        }

        $wasVisible = $animation->isVisible();
        // Déjà en ligne et republiée « maintenant » : pas de nouvelle validation.
        // Refusée ou masquée par l'équipe : elle repasse toujours par la validation.
        $moderation = match (true) {
            $wasVisible && ! $scheduledAt => Animation::MODERATION_APPROVED,
            $animation->isRejected(), $animation->isPendingReview() => $user->isStaff() ? Animation::MODERATION_APPROVED : Animation::MODERATION_PENDING,
            default => Animation::moderationStatusFor($user),
        };
        $pending = $moderation === Animation::MODERATION_PENDING;

        $animation->forceFill([
            'published_at' => $scheduledAt ?? ($animation->isPublished() ? $animation->published_at : now()),
            'moderation_status' => $moderation,
            'moderation_note' => null,
            'moderated_at' => $pending ? null : $animation->moderated_at,
            'rights_certified_at' => $animation->rights_certified_at ?? now(),
        ])->save();

        if ($pending) {
            AdminNotifier::send(MemberActivity::toReview($user, 1, 'animation'), $user);
        }

        $message = match (true) {
            $pending && $scheduledAt => 'Ton animation est envoyée à l’équipe pour validation. Sortie prévue le '.ComicStudio::localDate($scheduledAt).' ('.ComicStudio::timezoneLabel().'), une fois validée.',
            $pending => 'Ton animation est envoyée à l’équipe : elle sera en ligne après validation. Tu seras prévenu.',
            (bool) $scheduledAt => 'Ton animation sortira le '.ComicStudio::localDate($scheduledAt).' ('.ComicStudio::timezoneLabel().').',
            default => 'Ton animation est en ligne !',
        };

        return $back->with('success', $message);
    }
}
