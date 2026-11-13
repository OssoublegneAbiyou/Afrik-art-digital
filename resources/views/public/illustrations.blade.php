@php
    // Textes des enseignes (admin › Réglages › Décor).
    $tickerNight = setting('decor.bandeau_nuit', []);
    $tickerDay = setting('decor.bandeau_jour', []);
    $frames = $illustrations->take(2)->values();
    $filters = ['' => 'Tout'] + ($typeFilters ?? \App\Models\IllustrationType::filters());
@endphp

<x-world-layout world="neo" title="Illustrations" :description="'Toutes les illustrations publiées par les artistes du collectif '.$collectif['nom'].'.'">

    {{-- ===== Bandeau défilant ===== --}}
    <div class="nb-ticker nb-ticker--nuit only-nuit" aria-hidden="true">
        <div class="ticker">
            @for ($loop2 = 0; $loop2 < 2; $loop2++)
                @foreach ($tickerNight as $word)
                    <span>{{ $word }} <span class="ml-[2.2em]">✦</span></span>
                @endforeach
            @endfor
        </div>
    </div>
    <div class="nb-ticker nb-ticker--jour only-jour led-panel" aria-hidden="true">
        <div class="led-track">
            @for ($loop2 = 0; $loop2 < 2; $loop2++)
                <span>@foreach ($tickerDay as $word){{ $word }} ● @endforeach</span>
            @endfor
        </div>
    </div>

    {{-- ===== Hero ===== --}}
    <section class="nb-hero nb-gal-hero" aria-labelledby="nb-gal-title">
        <div class="nb-hero__deco" aria-hidden="true">
            <div class="scanlines only-nuit"></div>
            <div class="grid3d" style="height: 180px;"></div>
        </div>

        <div class="w-container nb-gal-hero__inner">
            <div class="relative z-[2]">
                <p class="neo-badge">
                    <span class="only-nuit">Galerie · ナイトマーケット</span>
                    <span class="only-jour">Galerie · Plein air</span>
                </p>
                <h1 id="nb-gal-title" class="nb-title mt-6">
                    Toutes les<br>
                    <span class="glitch">illustrations</span>
                </h1>
                <p class="nb-lead mt-6">
                    Toutes les images publiées par les illustrateurs du collectif. Entre dans une œuvre, puis dans l'univers de son artiste.
                </p>
                <p class="mt-6">
                    <span class="nb-count">{{ \App\Support\Fr::count($totalIllustrations, 'illustration publiée', 'illustrations publiées') }}</span>
                </p>
            </div>

            <div class="nb-frames" aria-hidden="true">
                <span class="nb-sign neon-sign nb-neon-pink flick only-nuit nb-gal-sign" style="left: 36%; top: -6px; font-size: 1.5rem; background: rgba(13, 2, 33, .85);">ギャラリー</span>
                <span class="nb-sign nb-gal-sign only-jour" style="left: 34%; top: -10px;"><span class="nb-screen-cyan"><span class="nb-dot blink"></span>OUVERT · 24/7</span></span>
                <div class="nb-sign hazard only-jour nb-secondary" style="left: 0; right: 0; bottom: 0; height: 22px; border: 3px solid #0D0221;"></div>
                @for ($i = 0; $i < 2; $i++)
                    <div class="nb-frame3d nb-frame3d--{{ $i + 1 }}">
                        @if ($frames->get($i))
                            <img src="{{ $frames->get($i)->thumbnailUrl() }}" alt="">
                        @else
                            <span class="neo-thumb neo-thumb--empty"></span>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
    </section>

    {{-- ===== Filtres + grille ===== --}}
    <section class="nb-section pt-10" aria-labelledby="nb-gal-grid-title">
        <div class="w-container">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <h2 id="nb-gal-grid-title" class="nb-h2">
                        {{ $activeType ? $filters[$activeType] : 'Toute la galerie' }}
                    </h2>
                    <p class="mt-2 text-sm text-[var(--muted)]">
                        {{ \App\Support\Fr::count($illustrations->total(), 'illustration') }}
                        @if ($illustrations->lastPage() > 1)
                            · page {{ $illustrations->currentPage() }} sur {{ $illustrations->lastPage() }}
                        @endif
                    </p>
                </div>

                <nav class="nb-sort" aria-label="Trier la galerie">
                    <span class="nb-sort__label">Trier</span>
                    @foreach ($sorts as $key => $label)
                        <a href="{{ route('public.illustrations', array_filter(['type' => $activeType, 'tri' => $key === 'recent' ? null : $key])) }}"
                            class="neo-btn neo-btn--ghost nb-sort__btn" @if ($sort === $key) aria-current="true" @endif>
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('public.illustrations') }}" class="nb-filters" role="group" aria-label="Filtrer par type">
                    @if ($sort !== 'recent')
                        <input type="hidden" name="tri" value="{{ $sort }}">
                    @endif
                    @foreach ($filters as $type => $label)
                        @php($pressed = ($type === '' && ! $activeType) || $type === $activeType)
                        <button
                            type="submit"
                            name="type"
                            value="{{ $type }}"
                            class="neo-btn neo-btn--ghost nb-filter"
                            aria-pressed="{{ $pressed ? 'true' : 'false' }}"
                        >
                            {{ $label }}
                            <small>{{ $type === '' ? $totalIllustrations : ($typeCounts[$type] ?? 0) }}</small>
                        </button>
                    @endforeach
                </form>
            </div>

            <div class="nb-tiles mt-10">
                @forelse ($illustrations as $illustration)
                    <x-public.illustration-card :illustration="$illustration" />
                @empty
                    <p class="nb-empty" style="grid-column: 1 / -1;">
                        {{ $activeType ? 'Aucune illustration dans cette catégorie pour le moment.' : 'Aucune illustration publiée pour le moment.' }}
                    </p>
                @endforelse
            </div>

            {{ $illustrations->onEachSide(1)->links('pagination.afrikart') }}
        </div>
    </section>
</x-world-layout>
