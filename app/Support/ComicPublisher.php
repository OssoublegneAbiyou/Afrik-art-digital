<?php

namespace App\Support;

use App\Models\Comic;
use App\Models\ComicChapter;
use App\Notifications\MemberActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sortie des chapitres : publication immédiate, validation par l'équipe ou programmation arrivée à échéance.
 * Les abonnés de la série sont prévenus UNE seule fois par chapitre (colonne notified_at, réservée atomiquement).
 */
class ComicPublisher
{
    /**
     * Le chapitre est-il désormais visible ? Alors : la fiche suit (validée et datée avec son premier chapitre),
     * last_chapter_at avance, et les abonnés sont prévenus (une fois). Renvoie true si le chapitre est sorti.
     */
    public static function release(ComicChapter $chapter): bool
    {
        $chapter = ComicChapter::withUnpublished()->find($chapter->id);
        $comic = $chapter ? Comic::withUnpublished()->find($chapter->comic_id) : null;

        if (! $chapter || ! $comic || ! $chapter->isVisible()) {
            return false;
        }

        // La fiche est validée en même temps que son premier chapitre (jamais une fiche masquée par l'équipe).
        if ($comic->isPendingReview()) {
            $comic->moderation_status = Comic::MODERATION_APPROVED;
            $comic->moderated_at = now();
        }

        if ($comic->published_at === null || $comic->published_at->gt($chapter->published_at)) {
            $comic->published_at = $chapter->published_at;
        }

        if ($comic->last_chapter_at === null || $comic->last_chapter_at->lt($chapter->published_at)) {
            $comic->last_chapter_at = $chapter->published_at;
        }

        $comic->save();

        if (! $comic->isVisible()) {
            return false;
        }

        $claimed = ComicChapter::withUnpublished()->whereKey($chapter->id)->whereNull('notified_at')
            ->update(['notified_at' => now()]);

        if ($claimed) {
            $chapter->setRelation('comic', $comic);
            self::notifyFollowers($comic, $chapter);
        }

        return true;
    }

    private static function notifyFollowers(Comic $comic, ComicChapter $chapter): void
    {
        try {
            $followers = $comic->followers()->whereKeyNot($comic->user_id)->whereNull('deactivated_at')->get();

            if ($followers->isNotEmpty()) {
                Notification::send($followers, MemberActivity::newComicChapter($chapter));
            }
        } catch (Throwable $exception) {
            Log::warning('Comic chapter notification failed', ['chapter' => $chapter->id, 'error' => $exception->getMessage()]);
        }
    }

    /**
     * Commande planifiée (chaque minute) : chapitres programmés devenus visibles → sortie ; chapitres programmés
     * encore en attente de validation à l'heure prévue → l'auteur est prévenu une fois.
     * Fenêtre de 3 jours : d'anciens chapitres jamais notifiés (imports) ne déclenchent pas de notifications.
     *
     * @return array{released: int, warned: int}
     */
    public static function releaseScheduled(): array
    {
        $released = 0;
        $warned = 0;

        ComicChapter::withUnpublished()
            ->whereNull('notified_at')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('published_at', '>=', now()->subDays(3))
            ->whereIn('moderation_status', [ComicChapter::MODERATION_APPROVED, ComicChapter::MODERATION_PENDING])
            ->orderBy('published_at')
            ->get()
            ->each(function (ComicChapter $chapter) use (&$released, &$warned) {
                if ($chapter->isApproved()) {
                    $released += self::release($chapter) ? 1 : 0;

                    return;
                }

                $claimed = ComicChapter::withUnpublished()->whereKey($chapter->id)->whereNull('retard_signale_at')
                    ->update(['retard_signale_at' => now()]);

                if ($claimed && ($author = $chapter->comic?->user)) {
                    $author->notify(MemberActivity::comicScheduledAwaitingReview($chapter));
                    $warned++;
                }
            });

        return ['released' => $released, 'warned' => $warned];
    }
}
