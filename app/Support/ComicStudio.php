<?php

namespace App\Support;

use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\ComicPage;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Données de l'espace artiste « Mes récits dessinés » : liste, statuts, stockage, statistiques, JSON des planches.
 */
class ComicStudio
{
    /** Récits de l'auteur (brouillons compris), avec chapitres et compteurs. */
    public static function comicsFor(User $user): Collection
    {
        return Comic::withUnpublished()->where('user_id', $user->id)
            ->with(['allChapters' => fn ($q) => $q->select(['id', 'comic_id', 'numero', 'published_at', 'moderation_status', 'view_count', 'pages_count'])])
            ->withCount(['likes', 'followers'])
            ->latest('updated_at')->get();
    }

    /** @return array{used: int, limit: int, ratio: int, usedLabel: string, limitLabel: string} */
    public static function storage(User $user): array
    {
        $used = ComicImages::usedBytes($user);
        $limit = ComicImages::quotaBytes();

        return [
            'used' => $used,
            'limit' => $limit,
            'ratio' => min(100, (int) round($used / $limit * 100)),
            'usedLabel' => ComicImages::formatBytes($used),
            'limitLabel' => ComicImages::formatBytes($limit),
        ];
    }

    /** Statut d'ensemble pour la liste : en ligne, programmé, en attente, refusé, masqué, brouillon. */
    public static function statusFor(Comic $comic): array
    {
        $chapters = $comic->relationLoaded('allChapters') ? $comic->allChapters : $comic->allChapters()->get();

        return match (true) {
            $comic->isRejected() => ['Masqué par l’équipe', 'off'],
            $chapters->contains(fn (ComicChapter $c) => $c->isVisible()) && $comic->isVisible() => ['En ligne', 'ok'],
            $chapters->contains(fn (ComicChapter $c) => $c->published_at && $c->isPendingReview()) => ['En attente de validation', 'pending'],
            $chapters->contains(fn (ComicChapter $c) => $c->isScheduled() && $c->isApproved()) => ['Programmé', 'pending'],
            $chapters->contains(fn (ComicChapter $c) => $c->published_at && $c->isRejected()) => ['Refusé par l’équipe', 'off'],
            default => ['Brouillon', 'draft'],
        };
    }

    /** Libellé du statut d'un chapitre, dates dans le fuseau des publications. */
    public static function chapterStatus(ComicChapter $chapter): string
    {
        return match (true) {
            $chapter->isDraft() => 'Brouillon',
            $chapter->isRejected() => 'Refusé par l’équipe',
            $chapter->isPendingReview() => $chapter->isScheduled()
                ? 'En attente de validation (sortie prévue le '.self::localDate($chapter->published_at).')'
                : 'En attente de validation',
            $chapter->isScheduled() => 'Programmé le '.self::localDate($chapter->published_at),
            default => 'Publié le '.self::localDate($chapter->published_at, false),
        };
    }

    public static function timezone(): string
    {
        $tz = (string) setting('bd.fuseau_horaire', 'Africa/Abidjan');

        return in_array($tz, timezone_identifiers_list(), true) ? $tz : 'Africa/Abidjan';
    }

    public static function timezoneLabel(): string
    {
        return self::timezone() === 'Europe/Paris' ? 'heure de Paris' : 'heure d’Abidjan';
    }

    public static function localDate(?CarbonInterface $date, bool $withTime = true): string
    {
        if (! $date) {
            return '';
        }

        return Carbon::instance($date)->setTimezone(self::timezone())->locale('fr')
            ->translatedFormat($withTime ? 'j F Y à H\hi' : 'j F Y');
    }

    /** « 2026-11-20T18:00 » saisi dans le fuseau des publications → date de l'application (UTC). */
    public static function parseLocal(string $value): ?Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d\TH:i', $value, self::timezone())->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    /** Numéro proposé pour un nouveau chapitre : dernier + 1. */
    public static function nextNumber(Comic $comic): float
    {
        $last = (float) ($comic->allChapters()->withTrashed()->max('numero') ?? 0);

        return floor($last) + 1;
    }

    /**
     * Statistiques : abonnés, vues par chapitre, taux de lecture complète (progression ≥ 90 % / lecteurs).
     */
    public static function stats(Comic $comic): array
    {
        $chapters = $comic->allChapters()->get();
        $progress = DB::table('comic_reading_progress')
            ->whereIn('comic_chapter_id', $chapters->pluck('id'))
            ->groupBy('comic_chapter_id')
            ->selectRaw('comic_chapter_id, COUNT(*) as lecteurs, SUM(CASE WHEN pourcentage >= 90 THEN 1 ELSE 0 END) as complets')
            ->get()->keyBy('comic_chapter_id');

        $rows = $chapters->map(function (ComicChapter $chapter) use ($progress) {
            $readers = (int) ($progress[$chapter->id]->lecteurs ?? 0);
            $complete = (int) ($progress[$chapter->id]->complets ?? 0);

            return [
                'chapter' => $chapter,
                'views' => (int) $chapter->view_count,
                'readers' => $readers,
                'complete' => $complete,
                'rate' => $readers > 0 ? (int) round($complete / $readers * 100) : null,
            ];
        });

        $readers = $rows->sum('readers');

        return [
            'followers' => $comic->followers()->count(),
            'likes' => $comic->likes()->count(),
            'views' => $rows->sum('views'),
            'readers' => $readers,
            'rate' => $readers > 0 ? (int) round($rows->sum('complete') / $readers * 100) : null,
            'chapters' => $rows,
        ];
    }

    /** JSON d'une planche pour l'assistant (jamais le chemin de l'original). */
    public static function pagePayload(ComicPage $page, Comic $comic, ComicChapter $chapter): array
    {
        $params = [$comic, $chapter, $page];

        return [
            'id' => $page->id,
            'ordre' => (int) $page->ordre,
            'nom' => $page->nom_fichier,
            'statut' => $page->statut_traitement,
            'erreur' => $page->erreur,
            'miniature' => $page->isReady() ? $page->thumbnailUrl() : null,
            'largeur' => $page->largeur,
            'hauteur' => $page->hauteur,
            'tranches' => $page->tranches ? count($page->tranches) : 0,
            'anime' => $page->isAnimated(),
            'alt' => $page->alt,
            'double_page' => (bool) $page->double_page,
            'taille' => (int) $page->taille_octets,
            'urls' => [
                'update' => route('comics.studio.pages.update', $params),
                'replace' => route('comics.studio.pages.replace', $params),
                'destroy' => route('comics.studio.pages.destroy', $params),
            ],
        ];
    }

    /** État d'un chapitre pour l'assistant : planches + compteurs + stockage. */
    public static function chapterPayload(Comic $comic, ComicChapter $chapter, User $user): array
    {
        $pages = $chapter->pages()->get();

        return [
            'pages' => $pages->map(fn (ComicPage $page) => self::pagePayload($page, $comic, $chapter))->values(),
            'count' => $pages->count(),
            'bytes' => (int) $pages->sum('taille_octets'),
            'pending' => $pages->where('statut_traitement', ComicPage::PENDING)->count(),
            'failed' => $pages->where('statut_traitement', ComicPage::FAILED)->count(),
            'max' => ComicImages::maxPagesPerChapter(),
            'storage' => self::storage($user),
        ];
    }

    /** Supprime les fichiers et la ligne d'un chapitre (définitivement). */
    public static function deleteChapter(ComicChapter $chapter): void
    {
        $chapter->pages()->get()->each(function (ComicPage $page) {
            ComicImages::deleteFiles($page);
            $page->delete();
        });

        $chapter->reports()->delete();
        $chapter->forceDelete();
    }

    /** Supprime un récit entier : planches, couverture, chapitres, abonnements, favoris, signalements. */
    public static function deleteComic(Comic $comic): void
    {
        $comic->allChapters()->withTrashed()->get()->each(fn (ComicChapter $chapter) => self::deleteChapter($chapter));

        if ($comic->couverture) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($comic->couverture);
        }

        $comic->favorites()->delete();
        $comic->reports()->delete();
        $comic->likes()->delete();
        $comic->followers()->detach();
        $comic->forceDelete();
    }

    /** Recalcule last_chapter_at après la suppression ou le retrait d'un chapitre. */
    public static function refreshLastChapter(Comic $comic): void
    {
        $last = ComicChapter::withUnpublished()->where('comic_id', $comic->id)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where('moderation_status', ComicChapter::MODERATION_APPROVED)
            ->max('published_at');

        $comic->forceFill(['last_chapter_at' => $last])->save();
    }
}
