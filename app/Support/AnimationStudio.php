<?php

namespace App\Support;

use App\Models\Animation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Données de l'espace artiste « Mes animations » : liste, statuts, état de conversion (JSON), statistiques, suppression.
 * Dates de publication dans le fuseau des récits dessinés (bd.fuseau_horaire), comme en Phase 1.
 */
class AnimationStudio
{
    public const PROCESSING_LABELS = [
        Animation::QUEUED => 'En file…',
        Animation::PROCESSING => 'Conversion…',
        Animation::READY => 'Prêt',
        Animation::FAILED => 'Échec',
    ];

    /** Animations de l'auteur (brouillons compris), avec compteurs. */
    public static function animationsFor(User $user): Collection
    {
        return Animation::withUnpublished()->where('user_id', $user->id)
            ->withCount(['likes', 'favorites'])
            ->latest('updated_at')->get();
    }

    public static function processingLabel(Animation $animation): string
    {
        return self::PROCESSING_LABELS[$animation->statut_traitement] ?? 'En file…';
    }

    public static function isProcessing(Animation $animation): bool
    {
        return in_array($animation->statut_traitement, [Animation::QUEUED, Animation::PROCESSING], true);
    }

    /** Statut de publication : [libellé, ton] (ok, pending, off, draft). */
    public static function statusFor(Animation $animation): array
    {
        return match (true) {
            $animation->isDraft() => ['Brouillon', 'draft'],
            $animation->isRejected() => ['Refusée ou masquée par l’équipe', 'off'],
            $animation->isPendingReview() => [$animation->isScheduled()
                ? 'En attente de validation (sortie prévue le '.ComicStudio::localDate($animation->published_at).')'
                : 'En attente de validation', 'pending'],
            $animation->isScheduled() => ['Programmée le '.ComicStudio::localDate($animation->published_at), 'pending'],
            ! $animation->isReady() => ['Publiée, en ligne dès la fin de la conversion', 'pending'],
            default => ['En ligne depuis le '.ComicStudio::localDate($animation->published_at, false), 'ok'],
        };
    }

    /** État de conversion pour le suivi en direct (JSON) : jamais le chemin de la source. */
    public static function payload(Animation $animation): array
    {
        return [
            'statut' => $animation->statut_traitement,
            'libelle' => self::processingLabel($animation),
            'erreur' => $animation->statut_traitement === Animation::FAILED ? $animation->erreur : null,
            'pret' => $animation->isReady(),
            'en_cours' => self::isProcessing($animation),
            'mp4' => $animation->isReady() ? $animation->mp4Url() : null,
            'poster' => $animation->isReady() ? $animation->posterUrl() : null,
            'duree' => $animation->durationLabel(),
            'largeur' => $animation->largeur,
            'hauteur' => $animation->hauteur,
            'taille' => ComicImages::formatBytes((int) $animation->taille_octets),
        ];
    }

    /** @return array{views: int, likes: int, favorites: int} */
    public static function stats(Animation $animation): array
    {
        return [
            'views' => (int) $animation->view_count,
            'likes' => $animation->likes()->count(),
            'favorites' => $animation->favorites()->count(),
        ];
    }

    /** Lien vers la fiche publique (ou l'aperçu de l'auteur), si la page existe. */
    public static function publicUrl(Animation $animation): ?string
    {
        return Route::has('animations.show') ? route('animations.show', $animation) : null;
    }

    /** Supprime une animation : fichiers (source et versions), favoris, signalements, j'aime. */
    public static function delete(Animation $animation): void
    {
        AnimationMedia::deleteFiles($animation);
        $animation->favorites()->delete();
        $animation->reports()->delete();
        $animation->likes()->delete();
        $animation->forceDelete();
    }
}
