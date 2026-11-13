<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Document;
use App\Models\Genre;
use App\Models\Illustration;
use App\Models\IllustrationType;
use App\Models\PlatformSetting;
use App\Models\Writer;
use App\Support\Featured;
use App\Support\ViewCounter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicController extends Controller
{
    public function index(): View
    {
        $artists = $this->artistsQuery()->get();
        $writers = $this->writersQuery()->get();
        $featuredArtist = $this->featuredArtist($artists);
        $featuredWriter = $this->featuredWriter($writers);
        $dailyIllustrations = $this->dailyIllustrations();
        $dailyDocuments = $this->dailyDocuments();

        // « Le fil des traits & des mots » : illustrations et récits entremêlés.
        $feed = $this->interleave($dailyIllustrations->take(6), $dailyDocuments->take(3));

        // Nouveaux chapitres de récits dessinés (BD / MANGA / WEBTOON), un par récit, glissés dans le fil.
        foreach ($this->latestComicChapters()->values() as $i => $chapter) {
            $feed->splice(min($feed->count(), 2 + $i * 4), 0, [$chapter]);
        }

        // Dernières animations (type « ANIMATION »), glissées elles aussi dans le fil.
        foreach ($this->latestAnimations()->values() as $i => $animation) {
            $feed->splice(min($feed->count(), 4 + $i * 5), 0, [$animation]);
        }

        $totals = [
            'artists' => $artists->count(),
            'writers' => $writers->count(),
            'illustrations' => $artists->sum(fn (Artist $artist) => $artist->illustrations->count()),
            'documents' => $writers->sum(fn (Writer $writer) => $writer->documents->count()),
        ];
        $collectiveText = PlatformSetting::collectiveText();

        return view('public.index', compact(
            'artists', 'writers', 'featuredArtist', 'featuredWriter',
            'dailyIllustrations', 'dailyDocuments', 'feed', 'totals', 'collectiveText'
        ));
    }

    public function about(): View
    {
        return view('public.about', [
            'collectiveText' => PlatformSetting::collectiveText(),
        ]);
    }

    public function artists(): View
    {
        $artists = $this->artistsQuery()->get();
        $featuredArtist = $this->featuredArtist($artists);
        $featuredIllustration = $featuredArtist?->illustrations->sortByDesc('created_at')->first();

        return view('public.artists', compact('artists', 'featuredArtist', 'featuredIllustration'));
    }

    public function writers(): View
    {
        $writers = $this->writersQuery()->get();
        $featuredWriter = $this->featuredWriter($writers);
        $documents = Document::with('writer.user')
            ->whereHas('writer.user', fn ($query) => $query->where('account_type', 'writer'))
            ->latest()
            ->get();
        $genreCounts = collect(Genre::options())
            ->map(fn ($label, $genre) => $documents->where('genre', $genre)->count());
        $mainGenres = $writers->mapWithKeys(fn (Writer $writer) => [$writer->id => $this->mainGenre($writer)]);

        return view('public.writers', compact('writers', 'featuredWriter', 'documents', 'genreCounts', 'mainGenres'));
    }

    public const GALLERY_PER_PAGE = 24;

    public const GALLERY_SORTS = [
        'recent' => 'Récent',
        'populaire' => 'Populaire',
    ];

    public function illustrations(Request $request): View
    {
        $published = fn () => Illustration::query()
            ->whereHas('artist.user', fn ($query) => $query->where('account_type', 'artist'));

        $typeFilters = IllustrationType::filters();
        $activeTypeSlugs = array_keys(IllustrationType::actifs()->all());
        $activeType = array_key_exists((string) $request->query('type'), $typeFilters) ? $request->query('type') : null;
        $sort = array_key_exists((string) $request->query('tri'), self::GALLERY_SORTS) ? $request->query('tri') : 'recent';

        $query = $published()->with('artist.user')->withCount('likes');

        if ($activeType === Illustration::OTHER_TYPE) {
            // « Autre » regroupe aussi les œuvres sans type (publiées avant que le type soit obligatoire)
            // et celles d'un type désactivé ou inconnu.
            $query->where(fn ($q) => $q->whereNull('type')->orWhereNotIn('type', $activeTypeSlugs));
        } elseif ($activeType) {
            $query->where('type', $activeType);
        }

        // Tri « Récent » : les œuvres épinglées (admin › Mises en avant) remontent en tête, dans leur ordre.
        if ($sort !== 'populaire' && ($pinnedIds = Featured::pinnedIds()) !== []) {
            $query->orderByRaw('case '.collect($pinnedIds)->map(fn ($id, $rank) => 'when '.$query->qualifyColumn('id').' = '.(int) $id.' then '.$rank)->implode(' ').' else '.count($pinnedIds).' end');
        }

        $sort === 'populaire'
            ? $query->orderByDesc('likes_count')->orderByDesc('created_at')->orderByDesc('id')
            : $query->orderByDesc('created_at')->orderByDesc('id');

        $illustrations = $query->paginate(self::GALLERY_PER_PAGE)->withQueryString();

        // Comptages par type calculés en base, sans charger les œuvres.
        $countsByType = $published()
            ->selectRaw("coalesce(type, '".Illustration::OTHER_TYPE."') as kind, count(*) as total")
            ->groupBy('kind')
            ->pluck('total', 'kind');
        $typeCounts = collect($typeFilters)->map(fn ($label, $type) => $type === Illustration::OTHER_TYPE
            ? (int) $countsByType->except($activeTypeSlugs)->sum()
            : (int) ($countsByType[$type] ?? 0));
        $totalIllustrations = (int) $countsByType->sum();
        $sorts = self::GALLERY_SORTS;

        // Filtre « Animé » (?format=anime) : la grille liste les animations prêtes ; sans filtre, un aperçu
        // des dernières animations est glissé en tête de la galerie. Paramètre distinct de « type » pour ne
        // jamais entrer en conflit avec un type d'illustration créé par l'équipe.
        $animationsEnabled = \App\Http\Middleware\EnsureAnimationsEnabled::enabled();
        $animated = $animationsEnabled && $request->query('format') === self::ANIMATED_FILTER;
        $animationsTotal = $animationsEnabled ? \App\Models\Animation::query()->listed()->count() : 0;
        $animations = null;
        $latestAnimations = collect();

        if ($animated) {
            $activeType = null;
            $animationQuery = \App\Models\Animation::query()->listed()->with(['user', 'artist'])->withCount('likes');
            $sort === 'populaire'
                ? $animationQuery->orderByDesc('likes_count')->orderByDesc('view_count')->orderByDesc('published_at')->orderByDesc('id')
                : $animationQuery->orderByDesc('published_at')->orderByDesc('id');
            $animations = $animationQuery->paginate(self::GALLERY_PER_PAGE)->withQueryString();
        } elseif ($animationsTotal > 0 && ! $activeType && $illustrations->currentPage() === 1) {
            $latestAnimations = \App\Models\Animation::query()->listed()->with(['user', 'artist'])->withCount('likes')
                ->orderByDesc('published_at')->orderByDesc('id')->take(4)->get();
        }

        return view('public.illustrations', compact(
            'illustrations', 'activeType', 'typeCounts', 'typeFilters', 'totalIllustrations', 'sort', 'sorts',
            'animated', 'animations', 'animationsTotal', 'latestAnimations'
        ));
    }

    /** Valeur du filtre « Animé » de la galerie (/illustrations?format=anime). */
    public const ANIMATED_FILTER = 'anime';

    public function literaryWorks(): View
    {
        $documents = Document::with('writer.user')
            ->whereHas('writer.user', fn ($query) => $query->where('account_type', 'writer'))
            ->latest()
            ->get();

        return view('public.literary-works', compact('documents'));
    }

    public function showArtist(Artist $artist): View
    {
        $artist->load(['user', 'illustrations', 'portfolios.items.illustration']);

        $currentUser = auth()->user();
        $isFollowingArtist = $currentUser
            ? $currentUser->followedArtists()->where('artist_id', $artist->id)->exists()
            : false;
        $favoriteIllustrationIds = $currentUser
            ? $currentUser->favoriteIllustrations()
                ->whereIn('illustration_id', $artist->illustrations->pluck('id'))
                ->pluck('illustration_id')
                ->all()
            : [];
        $isOwner = $currentUser?->id === $artist->user_id;
        $isFeaturedToday = $this->featuredArtist($this->artistsQuery()->get())?->is($artist) ?? false;

        // « Ses récits dessinés » (masqué si la rubrique est désactivée).
        $comics = \App\Http\Middleware\EnsureComicsEnabled::enabled()
            ? \App\Models\Comic::query()->withVisibleChapters()
                ->where('user_id', $artist->user_id)
                ->with(['user', 'artist'])
                ->withCount(['chapters', 'likes'])
                ->orderByRaw('coalesce(last_chapter_at, published_at) desc')
                ->get()
            : collect();

        // « Ses animations » (masqué si les animations sont désactivées).
        $animations = \App\Http\Middleware\EnsureAnimationsEnabled::enabled()
            ? \App\Models\Animation::query()->listed()
                ->where('user_id', $artist->user_id)
                ->with(['user', 'artist'])
                ->withCount('likes')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('public.artist-show', compact('artist', 'isFollowingArtist', 'favoriteIllustrationIds', 'isOwner', 'isFeaturedToday', 'comics', 'animations'));
    }

    public function showIllustration(Illustration $illustration): View
    {
        $illustration->load('artist.user');
        ViewCounter::record($illustration);
        $isOwner = $illustration->isOwnedBy(auth()->user());

        $artist = $illustration->artist;
        $artistIllustrations = Illustration::with('artist.user')
            ->where('artist_id', $artist->id)
            ->where('id', '!=', $illustration->id)
            ->latest()
            ->get();
        $suggestedIllustrations = Illustration::with('artist.user')
            ->where('artist_id', '!=', $artist->id)
            ->whereHas('artist.user', fn ($query) => $query->where('account_type', 'artist'))
            ->latest()
            ->take(8)
            ->get();

        // Navigation œuvre précédente / suivante dans la vitrine de l'artiste.
        $previousIllustration = Illustration::where('artist_id', $artist->id)
            ->where('id', '<', $illustration->id)
            ->orderByDesc('id')
            ->first();
        $nextIllustration = Illustration::where('artist_id', $artist->id)
            ->where('id', '>', $illustration->id)
            ->orderBy('id')
            ->first();
        $immersivePortfolio = $artist->portfolios()
            ->whereHas('items', fn ($query) => $query->where('illustration_id', $illustration->id))
            ->first();

        $currentUser = auth()->user();
        $isFavorite = $currentUser
            ? $currentUser->favoriteIllustrations()->where('illustration_id', $illustration->id)->exists()
            : false;

        return view('public.illustration-show', compact(
            'illustration', 'artist', 'artistIllustrations', 'suggestedIllustrations', 'isFavorite',
            'previousIllustration', 'nextIllustration', 'immersivePortfolio', 'isOwner'
        ));
    }

    public function showWriter(Writer $writer): View
    {
        $writer->load(['user', 'documents']);

        $currentUser = auth()->user();
        $bookmarkedDocumentIds = $currentUser
            ? $currentUser->bookmarkedDocuments()
                ->whereIn('document_id', $writer->documents->pluck('id'))
                ->pluck('document_id')
                ->all()
            : [];
        $mainGenre = $this->mainGenre($writer);
        $isOwner = $currentUser?->id === $writer->user_id;

        return view('public.writer-show', compact('writer', 'bookmarkedDocumentIds', 'mainGenre', 'isOwner'));
    }

    // Les comptes administrateurs n'apparaissent publiquement que s'ils ont publié quelque chose.
    private function artistsQuery()
    {
        return Artist::with(['user', 'illustrations', 'portfolios'])
            ->whereHas('user', fn ($query) => $query->where('account_type', 'artist'))
            ->where(fn ($query) => $query
                ->whereHas('user', fn ($user) => $user->where('role', \App\Models\User::ROLE_MEMBER))
                ->orHas('illustrations'))
            ->latest();
    }

    private function writersQuery()
    {
        return Writer::with(['user', 'documents'])
            ->whereHas('user', fn ($query) => $query->where('account_type', 'writer'))
            ->where(fn ($query) => $query
                ->whereHas('user', fn ($user) => $user->where('role', \App\Models\User::ROLE_MEMBER))
                ->orHas('documents'))
            ->latest();
    }

    // Artiste / plume du jour : planification datée > mode manuel > rotation automatique (voir App\Support\Featured).
    // Le membre mis en avant est prévenu une fois par jour (FeaturedNotifier).
    private function featuredArtist(Collection $artists): ?Artist
    {
        return Featured::artist($artists);
    }

    private function featuredWriter(Collection $writers): ?Writer
    {
        return Featured::writer($writers);
    }

    /**
     * Genre le plus représenté dans les œuvres d'un auteur (null si aucun genre renseigné).
     */
    private function mainGenre(Writer $writer): ?string
    {
        return $writer->documents
            ->pluck('genre')
            ->filter(fn ($genre) => Genre::findBySlug($genre) !== null)
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();
    }

    /**
     * Œuvres du « Fil » de l'accueil : les œuvres épinglées d'abord (dans l'ordre choisi par l'équipe),
     * puis le tirage du jour.
     */
    private function dailyIllustrations(): Collection
    {
        $pinned = Featured::pinnedIllustrations();

        return $pinned->concat($this->dailyShuffle(
            Illustration::with('artist.user')
                ->whereHas('artist.user', fn ($query) => $query->where('account_type', 'artist'))
                ->whereNotIn('id', $pinned->pluck('id'))
                ->get()
        ))->take(8)->values();
    }

    /**
     * Derniers chapitres sortis (un par récit visible), pour « Le fil » de l'accueil.
     */
    private function latestComicChapters(int $limit = 3): Collection
    {
        if (! \App\Http\Middleware\EnsureComicsEnabled::enabled()) {
            return collect();
        }

        $comicIds = \App\Models\Comic::query()->whereHas('user')->pluck('id');

        return \App\Models\ComicChapter::query()
            ->whereIn('comic_id', $comicIds)
            ->with('comic.user')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(30)
            ->get()
            ->unique('comic_id')
            ->take($limit)
            ->values();
    }

    /**
     * Dernières animations publiées et prêtes, pour « Le fil » de l'accueil.
     */
    private function latestAnimations(int $limit = 2): Collection
    {
        if (! \App\Http\Middleware\EnsureAnimationsEnabled::enabled()) {
            return collect();
        }

        return \App\Models\Animation::query()->listed()
            ->with('user')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take($limit)
            ->get();
    }

    private function dailyDocuments(): Collection
    {
        return $this->dailyShuffle(
            Document::with('writer.user')
                ->whereHas('writer.user', fn ($query) => $query->where('account_type', 'writer'))
                ->get()
        )->take(9);
    }

    private function dailyShuffle(Collection $items): Collection
    {
        $seed = today()->format('Y-m-d');

        return $items
            ->sortBy(fn ($item) => crc32($seed.'-'.$item->id))
            ->values();
    }

    /**
     * Place un récit toutes les deux illustrations : [illus, illus, récit, illus, illus, récit…].
     */
    private function interleave(Collection $illustrations, Collection $documents): Collection
    {
        $feed = collect();
        $documents = $documents->values();
        $docIndex = 0;

        foreach ($illustrations->values() as $index => $illustration) {
            $feed->push($illustration);

            if ($index % 2 === 1 && $documents->has($docIndex)) {
                $feed->push($documents->get($docIndex++));
            }
        }

        return $feed->concat($documents->slice($docIndex))->values();
    }
}
