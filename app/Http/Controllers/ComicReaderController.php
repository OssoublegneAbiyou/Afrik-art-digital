<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\ComicPage;
use App\Models\ComicReadingProgress;
use App\Support\ViewCounter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lecteur de récits dessinés (/recits-dessines/{slug}/lire/{numero}) et enregistrement de la progression.
 *
 * Images : le lecteur n'affiche que les versions WebP converties (jamais les originaux, disque privé), servies
 * depuis le disque public (/storage/comics/…). Les noms de fichiers sont versionnés (un remplacement crée un
 * nouveau fichier) : le serveur web peut donc les servir avec un cache long. À ajouter côté serveur :
 *
 *   Nginx :   location ^~ /storage/comics/ { add_header Cache-Control "public, max-age=31536000, immutable"; access_log off; }
 *   Apache :  <LocationMatch "^/storage/comics/"> Header set Cache-Control "public, max-age=31536000, immutable" </LocationMatch>
 *             (module headers ; ou un .htaccess dans storage/app/public/comics avec la même directive Header)
 */
class ComicReaderController extends Controller
{
    /** Seuil d'une vue : 3 pages atteintes ou 20 % du chapitre. */
    public const VIEW_MIN_PAGES = 3;
    public const VIEW_MIN_PERCENT = 20;

    public function read(Request $request, Comic $comic, string $numero): View
    {
        $user = $request->user();
        $preview = $comic->canBePreviewedBy($user);

        $chapters = ($preview ? $comic->allChapters() : $comic->chapters())->get()->values();
        $index = $chapters->search(fn (ComicChapter $chapter) => abs((float) $chapter->numero - (float) $numero) < 0.001);
        abort_if($index === false, 404);

        $comic->load(['user', 'artist']);
        $chapters->each->setRelation('comic', $comic);
        $chapter = $chapters->get($index);
        $previous = $index > 0 ? $chapters->get($index - 1) : null;
        $next = $chapters->get($index + 1);

        // Seules les pages prêtes (converties) sont montrées, à tout le monde.
        $pages = $chapter->pages()->where('statut_traitement', ComicPage::READY)
            ->where(fn ($q) => $q->whereNotNull('lecture')->orWhereNotNull('tranches'))
            ->get()->values();
        $pendingPages = $preview ? $chapter->pages()->where('statut_traitement', '!=', ComicPage::READY)->count() : 0;
        $total = $pages->count();

        $progress = $user ? ComicReadingProgress::where('user_id', $user->id)->where('comic_chapter_id', $chapter->id)->first() : null;
        $requested = (int) $request->query('page');
        $start = match (true) {
            $requested >= 1 => $requested,
            $progress !== null && ! $progress->isComplete() => (int) $progress->page_ordre,
            default => 1,
        };
        $start = max(1, min($start, max(1, $total)));

        $pageData = $pages->map(fn (ComicPage $page, int $i) => [
            'n' => $i + 1,
            'alt' => filled($page->alt) ? $page->alt : self::defaultAlt($comic, $chapter, $i + 1),
            'double' => (bool) $page->double_page,
            'slices' => $this->slices($page),
        ]);

        $sameStudio = Comic::query()->withVisibleChapters()
            ->where('user_id', $comic->user_id)
            ->whereKeyNot($comic->id)
            ->with(['user', 'artist'])
            ->withCount(['chapters', 'likes'])
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        $config = [
            'comic' => $comic->id,
            'chapter' => $chapter->id,
            'chapterNumber' => $chapter->routeNumber(),
            'chapterLabel' => $comic->isOneShot() ? null : $chapter->numberLabel(),
            'mode' => $comic->format_lecture === 'pages' ? 'pages' : 'vertical',
            'dir' => $comic->isRightToLeft() ? 'rtl' : 'ltr',
            'total' => $total,
            'start' => $start,
            'auth' => $user !== null,
            'progressUrl' => route('comics.progress'),
            'sensitive' => (bool) $comic->contenu_sensible,
            'viewPages' => self::VIEW_MIN_PAGES,
            'viewPercent' => self::VIEW_MIN_PERCENT,
            'nextUrl' => $next ? route('comics.read', [$comic, $next->routeNumber()]) : null,
            'prevUrl' => $previous ? route('comics.read', [$comic, $previous->routeNumber()]) : null,
            'title' => $comic->titre,
        ];

        return view('comics.reader', [
            'comic' => $comic,
            'chapter' => $chapter,
            'pages' => $pageData,
            'total' => $total,
            'start' => $start,
            'previous' => $previous,
            'next' => $next,
            'preview' => $preview,
            'pendingPages' => $pendingPages,
            'isOwner' => $comic->isOwnedBy($user),
            'isFollowing' => $user ? $comic->followers()->whereKey($user->id)->exists() : false,
            'sameStudio' => $sameStudio,
            'config' => $config,
            // Avertissement « contenu sensible » déjà accepté (sans JavaScript : ?avertissement=lu).
            'warningAccepted' => $request->query('avertissement') === 'lu',
        ]);
    }

    /**
     * Progression (membres) et comptage des vues (tout le monde).
     * Champs : chapitre (id), page (1…n), pourcentage (0…100). Accepte du JSON (fetch) ou un formulaire
     * (FormData de navigator.sendBeacon, avec le champ _token vérifié par le middleware CSRF).
     */
    public function progress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chapitre' => ['required', 'integer', 'min:1'],
            'page' => ['required', 'integer', 'min:1', 'max:5000'],
            'pourcentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $user = $request->user();
        $chapter = ComicChapter::withUnpublished()->find($data['chapitre']);
        $comic = $chapter?->comic;

        $public = $chapter && $comic && $chapter->isVisible() && $comic->isVisible();
        abort_unless($public || ($comic && $comic->canBePreviewedBy($user)), 404);

        $total = max(1, $chapter->pages()->where('statut_traitement', ComicPage::READY)->count());
        $page = min((int) $data['page'], $total);
        $percent = (int) $data['pourcentage'];

        $counted = false;

        if ($public && ($page >= self::VIEW_MIN_PAGES || $percent >= self::VIEW_MIN_PERCENT)) {
            $chapter->setRelation('comic', $comic);
            $counted = ViewCounter::recordDaily($chapter);
            ViewCounter::recordDaily($comic);
        }

        if ($user) {
            ComicReadingProgress::updateOrCreate(
                ['user_id' => $user->id, 'comic_chapter_id' => $chapter->id],
                ['page_ordre' => $page, 'pourcentage' => $percent],
            );
        }

        return response()->json(['saved' => $user !== null, 'view' => $counted]);
    }

    public static function defaultAlt(Comic $comic, ComicChapter $chapter, int $n): string
    {
        return $comic->isOneShot()
            ? 'Page '.$n.' de '.$comic->titre
            : 'Page '.$n.' du chapitre '.$chapter->numberLabel().' de '.$comic->titre;
    }

    /**
     * Images d'une page : la version de lecture, ou les tranches empilées (images très hautes découpées).
     *
     * @return list<array{url: string, width: int|null, height: int|null}>
     */
    private function slices(ComicPage $page): array
    {
        $tranches = collect($page->tranches ?? [])
            ->filter(fn ($slice) => is_array($slice) && filled($slice['chemin'] ?? null))
            ->map(fn ($slice) => [
                'url' => asset('storage/'.$slice['chemin']),
                'width' => isset($slice['largeur']) ? (int) $slice['largeur'] : null,
                'height' => isset($slice['hauteur']) ? (int) $slice['hauteur'] : null,
            ])
            ->values()
            ->all();

        return $tranches !== [] ? $tranches : [[
            'url' => $page->readingUrl(),
            'width' => $page->largeur ? (int) $page->largeur : null,
            'height' => $page->hauteur ? (int) $page->hauteur : null,
        ]];
    }
}
