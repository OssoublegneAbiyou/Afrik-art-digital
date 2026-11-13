<?php

namespace App\Support;

use App\Models\Animation;
use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\Document;
use App\Models\Illustration;
use App\Notifications\MemberActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Validation des œuvres et retrait après signalement (écrans « À valider » et « Signalements »).
 * Chaque décision prévient l'auteur par une notification MemberActivity.
 *
 * Récits dessinés : chaque chapitre passe par la file (type « comic_chapter ») ; la fiche du récit (« comic »)
 * est validée avec son premier chapitre et peut être masquée en entier depuis les signalements.
 */
class Moderation
{
    /** Types d'œuvres modérables, tels qu'ils apparaissent dans les URL. */
    public const TYPES = [
        'illustration' => Illustration::class,
        'document' => Document::class,
        'comic' => Comic::class,
        'comic_chapter' => ComicChapter::class,
        'animation' => Animation::class,
    ];

    public static function find(string $type, int $id): Model
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type]::withUnpublished()->findOrFail($id);
    }

    public static function typeOf(Model $work): string
    {
        return array_search($work::class, self::TYPES, true) ?: 'illustration';
    }

    /**
     * Soumissions en attente (hors brouillons), les plus anciennes d'abord.
     */
    public static function queue(): Collection
    {
        $illustrations = Illustration::withUnpublished()->with('artist.user')
            ->where('moderation_status', Illustration::MODERATION_PENDING)->whereNotNull('published_at')->get();
        $documents = Document::withUnpublished()->with('writer.user')
            ->where('moderation_status', Document::MODERATION_PENDING)->whereNotNull('published_at')->get();
        $chapters = self::pendingChapters()->with(['comic.user', 'pages'])->get();
        $animations = self::pendingAnimations()->with('user')->get();

        return $illustrations->concat($documents)->concat($chapters)->concat($animations)->sortBy('updated_at')->values();
    }

    /** Animations soumises en attente (requête, pour les compteurs). */
    public static function pendingAnimations()
    {
        return Animation::withUnpublished()
            ->where('moderation_status', Animation::MODERATION_PENDING)->whereNotNull('published_at');
    }

    /** Chapitres soumis en attente (requête, pour les compteurs). */
    public static function pendingChapters()
    {
        return ComicChapter::withUnpublished()
            ->where('moderation_status', ComicChapter::MODERATION_PENDING)->whereNotNull('published_at')
            ->whereHas('comic');
    }

    public static function approve(Model $work): void
    {
        $work->update([
            'moderation_status' => Illustration::MODERATION_APPROVED,
            'moderation_note' => null,
            'moderated_at' => now(),
        ]);

        if ($work instanceof ComicChapter) {
            // La fiche suit son premier chapitre ; sortie (abonnés) si la date est passée.
            $comic = Comic::withUnpublished()->find($work->comic_id);

            if ($comic?->isPendingReview()) {
                $comic->update(['moderation_status' => Comic::MODERATION_APPROVED, 'moderated_at' => now(), 'moderation_note' => null]);
            }

            ComicPublisher::release($work);
            $work->refresh()->comic?->user?->notify(MemberActivity::comicModerated($work, true));

            return;
        }

        if ($work instanceof Animation) {
            $work->user?->notify(MemberActivity::animationModerated($work->refresh(), true));

            return;
        }

        if ($work instanceof Comic) {
            $work->user?->notify(MemberActivity::comicSeriesModerated($work, true));

            return;
        }

        $work->authorUser()?->notify(MemberActivity::moderated($work, true));
    }

    /**
     * Refus d'une œuvre en attente, ou retrait d'une œuvre en ligne (après un signalement) :
     * elle n'est plus visible publiquement et l'auteur reçoit le motif.
     */
    public static function reject(Model $work, string $note): void
    {
        DB::transaction(fn () => $work->update([
            'moderation_status' => Illustration::MODERATION_REJECTED,
            'moderation_note' => $note,
            'moderated_at' => now(),
        ]));

        $notification = match (true) {
            $work instanceof ComicChapter => MemberActivity::comicModerated($work, false, $note),
            $work instanceof Comic => MemberActivity::comicSeriesModerated($work, false, $note),
            $work instanceof Animation => MemberActivity::animationModerated($work, false, $note),
            default => MemberActivity::moderated($work, false, $note),
        };

        $work->authorUser()?->notify($notification);
    }

    public static function publicUrl(Model $work): string
    {
        return match (true) {
            $work instanceof Illustration => route('public.illustration', $work),
            $work instanceof Document => route('documents.read', $work),
            $work instanceof ComicChapter => self::chapterUrl($work),
            $work instanceof Comic => Route::has('comics.show') ? route('comics.show', $work) : '#',
            $work instanceof Animation => Route::has('animations.show') ? route('animations.show', $work) : '#',
            default => '#',
        };
    }

    private static function chapterUrl(ComicChapter $chapter): string
    {
        $comic = $chapter->comic;

        if ($comic && Route::has('comics.read')) {
            return route('comics.read', [$comic, $chapter->routeNumber()]);
        }

        return $comic && Route::has('comics.show') ? route('comics.show', $comic) : '#';
    }

    public static function thumbnailUrl(Model $work): string
    {
        return match (true) {
            $work instanceof Illustration => $work->thumbnailUrl(),
            $work instanceof Document => $work->coverThumbnailUrl(),
            $work instanceof ComicChapter => $work->comic?->coverUrl()
                ?? $work->pages->first(fn ($page) => $page->isReady())?->thumbnailUrl() ?? '',
            $work instanceof Comic => (string) $work->coverUrl(),
            $work instanceof Animation => (string) $work->posterUrl(),
            default => '',
        };
    }

    public static function kindLabel(Model $work): string
    {
        return match (true) {
            $work instanceof Illustration => 'Illustration'.($work->typeLabel() ? ' · '.$work->typeLabel() : ''),
            $work instanceof Document => 'Texte'.($work->genreLabel() ? ' · '.$work->genreLabel() : ''),
            $work instanceof ComicChapter => 'Récit dessiné · '.($work->comic?->isOneShot()
                ? 'One-shot'
                : 'Série · chapitre '.$work->numberLabel()),
            $work instanceof Comic => 'Récit dessiné · '.$work->typeLabel(),
            $work instanceof Animation => 'Animation · '.$work->modeLabel().($work->durationLabel() ? ' · '.$work->durationLabel() : ''),
            default => 'Œuvre',
        };
    }
}
