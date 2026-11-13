@php
    // Étiquettes décoratives (pas des faits) : katakana la nuit, quartiers d'Abidjan le jour, en rotation.
    $katakana = ['アーティスト', 'スタジオ', 'ネオン', '作品', 'イラスト', 'アトリエ'];
    $quartiers = ['COCODY', 'PLATEAU', 'YOP CITY', 'TREICHVILLE', 'MARCORY', 'ADJAMÉ', 'KOUMASSI'];
    $tones = ['', 'neo-card--a2', 'neo-card--a3'];
    $featuredName = $featuredArtist?->user?->name ?? 'Artiste';
    $artistNames = $artists->map(fn ($artist) => $artist->user?->name ?? 'Artiste')->values();
@endphp

<x-world-layout world="neo" title="Illustrateurs" :description="'Tous les illustrateurs du collectif '.$collectif['nom'].' : galeries, portfolios immersifs et univers graphiques à découvrir.'">

    {{-- ===== Hero : la rue des illustrateurs ===== --}}
    <section class="nb-hero nb-street" aria-labelledby="nb-street-title">
        <div class="nb-hero__deco" aria-hidden="true">
            <div class="scanlines only-nuit"></div>
            <div class="nb-sun only-jour nb-secondary" style="right: 9%; top: 40px;"></div>
            <x-neo.skyline />
            <div class="grid3d"></div>
        </div>

        <div class="w-container nb-street__inner">
            <div class="nb-street__text">
                <p class="neo-badge">
                    <span class="only-nuit">Rue des illustrateurs · Nuit</span>
                    <span class="only-jour">Babi City · Plein jour</span>
                </p>
                <h1 id="nb-street-title" class="nb-title mt-6">
                    Chaque enseigne,<br>
                    <span class="glitch">un artiste.</span>
                </h1>
                <p class="nb-lead mt-6">
                    Explore la scène visuelle du collectif : chaque boutique ouvre sur un univers graphique, ses œuvres et ses portfolios immersifs.
                </p>
                <p class="mt-6 flex flex-wrap gap-3">
                    <span class="nb-count">{{ \App\Support\Fr::count($artists->count(), 'illustrateur') }}</span>
                    <span class="nb-count">{{ \App\Support\Fr::count($artists->sum(fn ($artist) => $artist->illustrations->count()), 'œuvre') }}</span>
                </p>
            </div>

            <div class="nb-street__scene">
                {{-- Décors de nuit --}}
                <div class="only-nuit" aria-hidden="true">
                    <span class="nb-sign nb-banner-v flick nb-secondary" style="left: 0; top: 10px;">イラスト通り</span>
                    <span class="nb-sign neon-sign nb-neon-pink nb-latin-neon flick" style="right: 0; bottom: 36px; font-size: 1.6rem; animation-delay: -1.3s; background: rgba(13, 2, 33, .85);">LIVE DROP</span>
                    <span class="nb-sign neon-sign nb-neon-cyan nb-secondary" style="right: -8px; top: -34px; font-size: 1.15rem; background: rgba(13, 2, 33, .85);">夜 · NUIT</span>
                    <span class="nb-sign nb-panel nb-secondary" style="left: 0; bottom: -26px; background: rgba(13, 2, 33, .85);">PLATEAU ↔ YOPOUGON</span>
                </div>

                {{-- Décors de jour --}}
                <div class="only-jour" aria-hidden="true">
                    <div class="nb-sign nb-secondary" style="left: 0; top: 0; width: 230px;">
                        <div class="led-panel nb-led">
                            <div class="led-track"><span>{{ setting('decor.led_illustrateurs') }}</span><span>{{ setting('decor.led_illustrateurs') }}</span></div>
                        </div>
                        <div class="nb-led__mast" style="height: 60px;"></div>
                    </div>
                    <div class="nb-sign nb-dir nb-secondary" style="right: 0; top: 10px;">
                        @foreach (array_slice(setting('decor.panneau_directions', []), 0, 3) as $direction)
                            <span><b>{{ ['↑', '←', '→'][$loop->index] }}</b>{{ $direction }}</span>
                        @endforeach
                    </div>
                    <div class="nb-sign nb-secondary" style="left: 2%; top: 140px; display: flex; align-items: flex-start; gap: 0;">
                        <div class="hazard nb-hazard-v" style="height: 190px;"></div>
                        <span class="nb-tag-pink" style="writing-mode: vertical-rl; margin-left: -4px; margin-top: 20px;">MAQUIS ART</span>
                    </div>
                    <span class="nb-sign nb-screen-cyan nb-drop-screen"><span class="nb-dot blink"></span>{{ setting('decor.ecran_drop') }}</span>
                    <span class="nb-sign nb-round-sign nb-secondary" style="right: 4%; top: 140px;"><span>ALLOCO<em>&amp;</em>CROQUIS</span></span>
                </div>

                @if ($featuredArtist)
                    {{-- Affiche de l'œuvre mise en avant de l'artiste du jour --}}
                    <a href="{{ $featuredIllustration ? route('public.illustration', $featuredIllustration) : route('public.artist', $featuredArtist) }}" class="nb-holo holo only-nuit">
                        <span class="nb-holo__top" aria-hidden="true">
                            <span>DROP_{{ str_pad((string) ($featuredIllustration?->id ?? $featuredArtist->id), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="nb-holo__rec"><span class="blink">●</span> REC</span>
                        </span>
                        @if ($featuredIllustration)
                            <img src="{{ $featuredIllustration->thumbnailUrl() }}" alt="{{ $featuredIllustration->title }} par {{ $featuredName }}" class="nb-holo__img">
                        @else
                            <span class="nb-holo__img nb-holo__img--empty block" aria-hidden="true"></span>
                        @endif
                        <span class="nb-holo__name block">{{ $featuredName }}</span>
                        <span class="nb-holo__star">★ DU JOUR</span>
                    </a>
                    <a href="{{ $featuredIllustration ? route('public.illustration', $featuredIllustration) : route('public.artist', $featuredArtist) }}" class="nb-poster only-jour">
                        <span class="nb-poster__tag neo-badge" aria-hidden="true">★ DU JOUR</span>
                        @if ($featuredIllustration)
                            <img src="{{ $featuredIllustration->thumbnailUrl() }}" alt="{{ $featuredIllustration->title }} par {{ $featuredName }}">
                        @endif
                        <span class="nb-poster__name block">{{ $featuredName }}</span>
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- ===== Bandeau « Artiste du jour » ===== --}}
    @if ($featuredArtist)
        <div class="w-container">
            <section class="nb-daily" aria-labelledby="nb-daily-title">
                <span aria-hidden="true">
                    <span class="nb-daily__kanji only-nuit">本日</span>
                    <span class="nb-daily__sun only-jour">☀ 12H</span>
                </span>
                <div class="nb-daily__body">
                    <h2 id="nb-daily-title" class="nb-daily__label">Artiste du jour</h2>
                    <p class="nb-daily__name">{{ $featuredName }}</p>
                    @if ($featuredArtist->bio)
                        <p class="nb-daily__bio">{{ $featuredArtist->bio }}</p>
                    @endif
                </div>
                <a href="{{ route('public.artist', $featuredArtist) }}" class="neo-btn nb-btn-invert">
                    Entrer dans la boutique <span aria-hidden="true">→</span>
                </a>
            </section>
        </div>
    @endif

    {{-- ===== Toutes les enseignes ===== --}}
    <section
        class="nb-section"
        aria-labelledby="nb-all-title"
        x-data="{
            q: '',
            names: @js($artistNames),
            norm(text) { return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim(); },
            match(name) { return this.norm(name).includes(this.norm(this.q)); },
            get visible() { return this.names.filter((n) => this.match(n)).length; },
        }"
    >
        <div class="w-container">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// 看板</span><span class="only-jour">// LES BOUTIQUES</span></p>
                    <h2 id="nb-all-title" class="nb-h2 mt-2">Toutes les enseignes</h2>
                </div>
                @if ($artists->isNotEmpty())
                    <div class="nb-search" role="search">
                        <label for="nb-artist-search">Rechercher un illustrateur</label>
                        <div class="nb-search__field">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                            <input id="nb-artist-search" type="search" class="neo-input" placeholder="Nom de l'artiste…" autocomplete="off" x-model="q">
                        </div>
                    </div>
                @endif
            </div>

            <p class="nb-sr" aria-live="polite" x-text="q ? (visible + (visible > 1 ? ' illustrateurs trouvés' : ' illustrateur trouvé')) : ''"></p>

            <div class="nb-cards">
                @forelse ($artists as $index => $artist)
                    @php
                        $name = $artist->user?->name ?? 'Artiste';
                        $thumbs = $artist->illustrations->sortByDesc('created_at')->take(2)->values();
                    @endphp
                    <a
                        href="{{ route('public.artist', $artist) }}"
                        class="neo-card {{ $tones[$index % 3] }}"
                        x-show="match(@js($name))"
                    >
                        <span class="neo-card__head">
                            <span class="neo-card__name">{{ $name }}</span>
                            <span class="neo-card__tag" aria-hidden="true">
                                <span class="only-nuit font-jp">{{ $katakana[$index % count($katakana)] }}</span>
                                <span class="only-jour">{{ $quartiers[$index % count($quartiers)] }}</span>
                            </span>
                        </span>
                        <span class="nb-card__body block">
                            <span class="nb-card__thumbs">
                                @for ($i = 0; $i < 2; $i++)
                                    @if ($thumbs->get($i))
                                        <img src="{{ $thumbs->get($i)->thumbnailUrl() }}" alt="{{ $thumbs->get($i)->title }} par {{ $name }}" class="neo-thumb" loading="lazy" decoding="async">
                                    @else
                                        <span class="neo-thumb neo-thumb--empty" aria-hidden="true"></span>
                                    @endif
                                @endfor
                            </span>
                            @if ($artist->bio)
                                <span class="nb-card__bio">{{ $artist->bio }}</span>
                            @endif
                            <span class="nb-card__meta">
                                <span class="nb-card__counts">
                                    <span>{{ \App\Support\Fr::count($artist->illustrations->count(), 'illustration') }}</span>
                                    @if ($artist->portfolios->isNotEmpty())
                                        <span>{{ \App\Support\Fr::count($artist->portfolios->count(), 'portfolio') }}</span>
                                    @endif
                                </span>
                                <span class="nb-card__go">Voir la boutique <span aria-hidden="true">→</span></span>
                            </span>
                        </span>
                    </a>
                @empty
                    <p class="nb-empty" style="grid-column: 1 / -1;">Aucun illustrateur pour le moment.</p>
                @endforelse
            </div>

            @if ($artists->isNotEmpty())
                <p class="nb-empty mt-8" x-show="q && visible === 0" x-cloak>
                    Aucune enseigne ne correspond à « <span x-text="q"></span> ».
                </p>
            @endif
        </div>
    </section>
</x-world-layout>
