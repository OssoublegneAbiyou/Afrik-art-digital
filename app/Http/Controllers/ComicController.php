<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\ComicPage;
use App\Models\ComicReadingProgress;
use App\Models\Genre;
use App\Notifications\MemberActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Récits dessinés, côté public : liste /recits-dessines, fiche d'un récit, suivre une série, favoris.
 * Le lecteur est dans ComicReaderController.
 */
class ComicController extends Controller
{
    public const PER_PAGE = 24;

    public const FILTERS = [
        '' => 'Tous',
        'one-shot' => 'One-shots',
        'serie' => 'Séries',
        'terminee' => 'Terminées',
    ];

    public const SORTS = [
        'recent' => 'Récents',
        'populaire' => 'Populaires',
        'maj' => 'Mis à jour',
    ];

    public function index(Request $request): View
    {
        $filter = array_key_exists((string) $request->query('type'), self::FILTERS) ? (string) $request->query('type') : '';
        $sort = array_key_exists((string) $request->query('tri'), self::SORTS) ? (string) $request->query('tri') : 'recent';

        // Genres réellement utilisés par des récits visibles (pas d'option vide dans le filtre).
        $genres = $this->listed()->whereNotNull('genre')->distinct()->pluck('genre')
            ->mapWithKeys(fn ($slug) => [$slug => Genre::labelFor($slug)])
            ->filter()
            ->sort();
        $genre = $genres->has((string) $request->query('genre')) ? (string) $request->query('genre') : null;

        $query = $this->filtered($filter, $genre)
            ->with(['user', 'artist'])
            ->withCount(['chapters', 'likes']);

        match ($sort) {
            'populaire' => $query->orderByDesc('likes_count')->orderByDesc('view_count')->orderByDesc('id'),
            'maj' => $query->orderByRaw('coalesce(last_chapter_at, published_at) desc')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $comics = $query->paginate(self::PER_PAGE)->withQueryString();

        // Comptages réels par filtre (avec le genre choisi).
        $counts = collect(self::FILTERS)->map(fn ($label, $key) => $this->filtered($key, $genre)->count());

        return view('comics.index', [
            'comics' => $comics,
            'filter' => $filter,
            'filters' => self::FILTERS,
            'counts' => $counts,
            'genre' => $genre,
            'genres' => $genres,
            'sort' => $sort,
            'sorts' => self::SORTS,
        ]);
    }

    public function show(Request $request, Comic $comic): View
    {
        $user = $request->user();
        $preview = $comic->canBePreviewedBy($user);

        $chapters = ($preview ? $comic->allChapters() : $comic->chapters())
            ->withCount(['pages as ready_pages_count' => fn ($q) => $q->where('statut_traitement', ComicPage::READY)])
            ->get();

        // Une fiche sans chapitre visible n'existe pas encore pour le public.
        abort_if($chapters->isEmpty() && ! $preview, 404);

        $comic->load(['user', 'artist']);
        $chapters->each->setRelation('comic', $comic);

        $progress = $user
            ? ComicReadingProgress::where('user_id', $user->id)->whereIn('comic_chapter_id', $chapters->pluck('id'))->get()->keyBy('comic_chapter_id')
            : collect();

        $sameStudio = $this->listed()
            ->where('user_id', $comic->user_id)
            ->whereKeyNot($comic->id)
            ->with(['user', 'artist'])
            ->withCount(['chapters', 'likes'])
            ->orderByDesc('published_at')
            ->take(6)
            ->get();

        return view('comics.show', [
            'comic' => $comic,
            'chapters' => $chapters,
            'progress' => $progress,
            'resume' => self::resumePoint($chapters, $progress),
            'preview' => $preview,
            'isOwner' => $comic->isOwnedBy($user),
            'isFollowing' => $user ? $comic->followers()->whereKey($user->id)->exists() : false,
            'isFavorite' => $user ? $comic->favorites()->where('user_id', $user->id)->exists() : false,
            'followersCount' => $comic->followers()->count(),
            'sameStudio' => $sameStudio,
        ]);
    }

    /**
     * Suivre / ne plus suivre une série. L'auteur est prévenu d'un nouvel abonné.
     */
    public function follow(Request $request, Comic $comic): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($comic->isVisible(), 404);

        $following = false;

        if (! $comic->isOwnedBy($user)) {
            $changes = $comic->followers()->toggle($user->id);
            $following = $changes['attached'] !== [];

            if ($following) {
                $comic->user?->notify(MemberActivity::newComicFollower($comic, $user));
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['following' => $following, 'count' => $comic->followers()->count()]);
        }

        return back()->with('comic_status', $following
            ? 'Tu suis « '.$comic->titre.' » : tu seras prévenu à chaque nouveau chapitre.'
            : 'Tu ne suis plus « '.$comic->titre.' ».');
    }

    /**
     * Ajouter / retirer des favoris (« Ma collection »).
     */
    public function favorite(Request $request, Comic $comic): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($comic->isVisible(), 404);

        $existing = $comic->favorites()->where('user_id', $user->id)->first();
        $existing ? $existing->delete() : $comic->favorites()->create(['user_id' => $user->id]);
        $favorite = ! $existing;

        if ($request->expectsJson()) {
            return response()->json(['favorite' => $favorite]);
        }

        return back()->with('comic_status', $favorite
            ? '« '.$comic->titre.' » est dans tes favoris.'
            : '« '.$comic->titre.' » a été retiré de tes favoris.');
    }

    /**
     * Où reprendre : le dernier chapitre ouvert s'il n'est pas terminé, sinon le chapitre suivant.
     * Renvoie null s'il n'y a rien à reprendre.
     *
     * @return array{chapter: \App\Models\ComicChapter, page: int, label: string}|null
     */
    public static function resumePoint(Collection $chapters, Collection $progress): ?array
    {
        $last = $progress->sortByDesc('updated_at')->first();

        if (! $last) {
            return null;
        }

        $chapters = $chapters->values();
        $index = $chapters->search(fn ($chapter) => $chapter->id === $last->comic_chapter_id);

        if ($index === false) {
            return null;
        }

        $chapter = $chapters->get($index);
        $isOneShot = $chapter->comic?->isOneShot();

        if (! $last->isComplete()) {
            return [
                'chapter' => $chapter,
                'page' => max(1, (int) $last->page_ordre),
                'label' => $isOneShot
                    ? 'Reprendre p. '.max(1, (int) $last->page_ordre)
                    : 'Reprendre ch. '.$chapter->numberLabel().', p. '.max(1, (int) $last->page_ordre),
            ];
        }

        $next = $chapters->get($index + 1);

        return $next ? ['chapter' => $next, 'page' => 1, 'label' => 'Continuer : ch. '.$next->numberLabel()] : null;
    }

    /** Récits visibles publiquement : fiche publiée et validée, au moins un chapitre visible. */
    private function listed(): Builder
    {
        return Comic::query()->withVisibleChapters()->whereHas('user');
    }

    private function filtered(string $filter, ?string $genre): Builder
    {
        return $this->listed()
            ->when($filter === 'one-shot', fn ($q) => $q->where('type', Comic::ONE_SHOT))
            ->when($filter === 'serie', fn ($q) => $q->where('type', Comic::SERIES))
            ->when($filter === 'terminee', fn ($q) => $q->where('type', Comic::SERIES)->where('statut_serie', 'terminee'))
            ->when($genre, fn ($q) => $q->where('genre', $genre));
    }
}
