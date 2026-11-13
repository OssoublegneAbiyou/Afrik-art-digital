@php
    $socialLinks = \App\Support\SocialLink::collect([
        'Instagram' => ['instagram', $artist->instagram],
        'X' => ['twitter', $artist->twitter],
        'Facebook' => ['facebook', $artist->facebook],
        'YouTube' => ['youtube', $artist->youtube],
        'Behance' => ['behance', $artist->behance],
        'Site web' => ['website', $artist->website],
    ]);
    $instagramUrl = \App\Support\SocialLink::url('instagram', $artist->instagram);
    $otherLinks = collect($socialLinks)->reject(fn ($link) => $link['label'] === 'Instagram')->values();
    $artistName = $artist->user?->name ?? 'Artiste';
    $initial = mb_strtoupper(mb_substr(trim($artistName), 0, 1)) ?: 'A';
    $avatarUrl = $artist->user?->google_avatar;
    $illustrationsCount = $artist->illustrations->count();
    $portfoliosCount = $artist->portfolios->count();
    $vitrine = $artist->illustrations->sortByDesc('created_at')->values();
    $roomKanji = ['第一室', '第二室', '第三室', '第四室', '第五室', '第六室'];
@endphp

<x-world-layout
    world="neo"
    :title="$artistName.' — Illustrateur'"
    :description="$artist->bio ?: 'Découvrez les illustrations et portfolios '.\App\Support\Fr::de($artistName).' sur '.$collectif['nom'].'.'"
    :image="$artist->illustrations->first()?->thumbnailUrl()"
>
    {{-- ===== Hero studio ===== --}}
    <section class="nb-hero nb-profile" aria-labelledby="nb-artist-name">
        <div class="nb-hero__deco" aria-hidden="true">
            @if ($artist->banner_path)
                <img src="{{ asset('storage/'.$artist->banner_path) }}" alt="" class="nb-profile__banner">
            @endif
            <div class="scanlines only-nuit"></div>
            <div class="hazard nb-hazard-strip only-jour"></div>
        </div>

        <div class="w-container">
            <div class="nb-profile__top">
                <nav class="nb-crumbs" aria-label="Fil d'Ariane">
                    <ol>
                        <li><a href="{{ route('public.index') }}">Accueil</a></li>
                        <li><a href="{{ route('public.artists') }}">Illustrateurs</a></li>
                        <li><span aria-current="page">{{ $artistName }}</span></li>
                    </ol>
                </nav>
                <div class="nb-profile__led" aria-hidden="true">
                    <div class="led-panel nb-led">
                        <div class="led-track">
                            <span>STUDIO {{ mb_strtoupper($artistName) }} · ABIDJAN ●</span>
                            <span>STUDIO {{ mb_strtoupper($artistName) }} · ABIDJAN ●</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nb-profile__main">
                <div class="relative z-[2]">
                    <div class="nb-id">
                        <div class="nb-avatar" aria-hidden="true">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="" referrerpolicy="no-referrer">
                            @else
                                {{ $initial }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="nb-badges">
                                <span class="neo-badge nb-badge-a2">Illustrateur</span>
                                @if ($isFeaturedToday)
                                    <span class="neo-badge">★ Artiste du jour</span>
                                @endif
                            </p>
                            <h1 id="nb-artist-name" class="nb-title nb-profile__name"><span class="glitch">{{ $artistName }}</span></h1>
                            <p class="nb-profile__sub" aria-hidden="true">
                                <span class="only-nuit font-jp">アーティスト · スタジオ</span>
                                <span class="only-jour">STUDIO · ABIDJAN</span>
                            </p>
                        </div>
                    </div>

                    @if ($artist->bio)
                        <p class="nb-bio">{{ $artist->bio }}</p>
                    @elseif ($isOwner)
                        <p class="nb-bio nb-bio--owner">
                            Ajoute ta bio depuis ton espace.
                            <a href="{{ route('dashboard') }}" class="underline decoration-2 underline-offset-4">Ouvrir mon espace</a>
                        </p>
                    @endif

                    <div class="nb-actions">
                        @auth
                                <form method="POST" action="{{ $isFollowingArtist ? route('artists.unfollow', $artist) : route('artists.follow', $artist) }}">
                                    @csrf
                                    @if ($isFollowingArtist)
                                        @method('DELETE')
                                    @endif
                                    <button type="submit" class="neo-btn {{ $isFollowingArtist ? 'neo-btn--outline' : 'neo-btn--a1' }}" aria-pressed="{{ $isFollowingArtist ? 'true' : 'false' }}">
                                        {{ $isFollowingArtist ? 'Ne plus suivre' : 'Suivre' }}
                                    </button>
                                </form>
                        @else
                            <a href="{{ route('login') }}" class="neo-btn neo-btn--a1">Connecte-toi pour suivre</a>
                        @endauth

                        @if ($instagramUrl)
                            <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" class="neo-btn neo-btn--ghost">
                                Instagram <span aria-hidden="true">↗</span>
                            </a>
                        @endif
                        @foreach ($otherLinks as $link)
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="neo-btn neo-btn--outline">
                                {{ $link['label'] }} <span aria-hidden="true">↗</span>
                            </a>
                        @endforeach
                    </div>

                    <x-share-buttons class="nb-share" :title="'Le studio '.\App\Support\Fr::de($artistName).' sur '.$collectif['nom']" label="Partager le studio" />
                </div>

                <div class="nb-cube-wrap nb-secondary" aria-hidden="true">
                    <div>
                        <div class="nb-cube spin3d">
                            <span>{{ $initial }}</span><span>{{ $initial }}</span><span>{{ $initial }}</span><span>{{ $initial }}</span><span></span><span></span>
                        </div>
                        <div class="nb-cube-shadow"></div>
                    </div>
                </div>
            </div>

            <dl class="nb-stats mt-10">
                <div class="nb-stat">
                    <dt class="nb-stat__l">{{ $illustrationsCount >= 2 ? 'Œuvres' : 'Œuvre' }}</dt>
                    <dd class="nb-stat__n">{{ $illustrationsCount }}</dd>
                </div>
                <div class="nb-stat">
                    <dt class="nb-stat__l">{{ $portfoliosCount >= 2 ? 'Portfolios' : 'Portfolio' }}</dt>
                    <dd class="nb-stat__n">{{ $portfoliosCount }}</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- ===== Galerie privée (portfolios immersifs, toujours de nuit) ===== --}}
    @if ($artist->portfolios->isNotEmpty() || $isOwner)
        <section class="nb-section pb-0" aria-labelledby="nb-private-title">
            <div class="w-container">
                <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// 秘密のギャラリー</span><span class="only-jour">// VISITE IMMERSIVE</span></p>
                <h2 id="nb-private-title" class="nb-h2 mt-2">Entrer dans la galerie privée</h2>

                <div class="mt-8 grid gap-8 lg:grid-cols-2">
                    @forelse ($artist->portfolios as $index => $portfolio)
                        @php($coverItem = $portfolio->items->first())
                        <a href="{{ route('artist-portfolios.show', $portfolio) }}" class="nb-private">
                            <span class="nb-private__cover" aria-hidden="true">
                                @if ($coverItem?->illustration)
                                    <img src="{{ $coverItem->illustration->thumbnailUrl() }}" alt="" loading="lazy">
                                @endif
                            </span>
                            <span class="nb-private__body">
                                <span class="nb-private__night">☾ Toujours de nuit</span>
                                <span class="nb-private__kicker">PORTFOLIO IMMERSIF · {{ \App\Support\Fr::count($portfolio->items->count(), 'salle') }}</span>
                                <span class="nb-private__title">{{ $portfolio->title }}</span>
                                @if ($portfolio->description)
                                    <span class="nb-private__meta">{{ \Illuminate\Support\Str::limit($portfolio->description, 140) }}</span>
                                @endif
                                <span class="nb-private__cta">Entrer dans la galerie privée <span aria-hidden="true">→</span></span>
                                <span class="nb-private__kanji" aria-hidden="true">{{ $roomKanji[$index % count($roomKanji)] }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="nb-empty" style="grid-column: 1 / -1;">
                            Tu n'as pas encore de portfolio immersif.
                            <a href="{{ route('artist-portfolios.create') }}" class="font-bold text-[var(--a1-text)] underline underline-offset-4">Créer mon premier portfolio</a>
                        </p>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    {{-- ===== Ses récits dessinés ===== --}}
    @if (($comics ?? collect())->isNotEmpty())
        <section class="nb-section pb-0" aria-labelledby="nb-comics-title">
            <div class="w-container">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// 漫画</span><span class="only-jour">// BD · MANGA · WEBTOON</span></p>
                        <h2 id="nb-comics-title" class="nb-h2 mt-2">Ses récits dessinés</h2>
                    </div>
                    <span class="nb-count">{{ \App\Support\Fr::count($comics->count(), 'récit') }}</span>
                </div>
                <div class="nb-tiles bd-grid mt-10">
                    @foreach ($comics as $comic)
                        @include('comics.partials.card', ['comic' => $comic, 'showAuthor' => false])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===== En vitrine ===== --}}
    <section class="nb-section" aria-labelledby="nb-vitrine-title">
        <div class="w-container">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// ショーウィンドウ</span><span class="only-jour">// LA VITRINE</span></p>
                    <h2 id="nb-vitrine-title" class="nb-h2 mt-2">En vitrine</h2>
                </div>
                <span class="nb-count">{{ \App\Support\Fr::count($illustrationsCount, 'illustration') }}</span>
            </div>

            <div class="nb-tiles mt-10">
                @forelse ($vitrine as $illustration)
                    @php($isFav = in_array($illustration->id, $favoriteIllustrationIds, true))
                    <x-public.illustration-card :illustration="$illustration" :show-artist="false">
                        @auth
                            <form method="POST" action="{{ $isFav ? route('illustrations.unfavorite', $illustration) : route('illustrations.favorite', $illustration) }}">
                                @csrf
                                @if ($isFav)
                                    @method('DELETE')
                                @endif
                                <button type="submit" class="nb-fav-mini" aria-pressed="{{ $isFav ? 'true' : 'false' }}">
                                    <span aria-hidden="true">{{ $isFav ? '♥' : '♡' }}</span>
                                    {{ $isFav ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
                                </button>
                            </form>
                        @endauth
                    </x-public.illustration-card>
                @empty
                    <p class="nb-empty" style="grid-column: 1 / -1;">
                        {{ $isOwner ? "Ta vitrine est vide : publie ta première illustration depuis ton espace." : "Cet artiste n'a pas encore publié d'illustrations." }}
                    </p>
                @endforelse
            </div>
        </div>
    </section>
</x-world-layout>
