@php
    use App\Support\Fr;

    $tickerNight = setting('decor.bandeau_nuit', []);
    $tickerDay = setting('decor.bandeau_jour', []);
    $total = (int) ($counts[''] ?? 0);
    $heading = $filter !== '' ? $filters[$filter] : 'Tous les récits';
    if ($genre) {
        $heading .= ' · '.$genres[$genre];
    }
    $params = fn (array $over = []) => array_filter(array_merge([
        'type' => $filter ?: null,
        'genre' => $genre,
        'tri' => $sort === 'recent' ? null : $sort,
    ], $over), fn ($v) => $v !== null && $v !== '');
@endphp

<x-world-layout world="neo" title="Récits dessinés" :description="'One-shots, BD, mangas et webtoons des illustrateurs du collectif '.$collectif['nom'].', à lire en ligne.'">

    {{-- ===== Bandeau défilant ===== --}}
    @if ($tickerNight)
        <div class="nb-ticker nb-ticker--nuit only-nuit" aria-hidden="true">
            <div class="ticker">
                @for ($loop2 = 0; $loop2 < 2; $loop2++)
                    @foreach ($tickerNight as $word)
                        <span>{{ $word }} <span class="ml-[2.2em]">✦</span></span>
                    @endforeach
                @endfor
            </div>
        </div>
    @endif
    @if ($tickerDay)
        <div class="nb-ticker nb-ticker--jour only-jour led-panel" aria-hidden="true">
            <div class="led-track">
                @for ($loop2 = 0; $loop2 < 2; $loop2++)
                    <span>@foreach ($tickerDay as $word){{ $word }} ● @endforeach</span>
                @endfor
            </div>
        </div>
    @endif

    {{-- ===== Hero ===== --}}
    <section class="nb-hero nb-gal-hero bd-hero" aria-labelledby="bd-title">
        <div class="nb-hero__deco" aria-hidden="true">
            <div class="scanlines only-nuit"></div>
            <div class="grid3d" style="height: 180px;"></div>
        </div>

        <div class="w-container bd-hero__inner">
            <div class="relative z-[2]">
                <p class="neo-badge">
                    <span class="only-nuit">One-shots · séries · 連載</span>
                    <span class="only-jour">One-shots · séries</span>
                </p>
                <h1 id="bd-title" class="nb-title mt-6">
                    <span class="glitch" data-text="RÉCITS DESSINÉS">RÉCITS DESSINÉS</span>
                </h1>
                <p class="nb-lead mt-6">
                    BD, mangas et webtoons des illustrateurs du collectif : des histoires courtes et des séries en chapitres, à lire sur téléphone comme sur ordinateur.
                </p>
                @if ($total > 0)
                    <p class="mt-6"><span class="nb-count">{{ Fr::count($total, 'récit publié', 'récits publiés') }}</span></p>
                @endif
            </div>

            <div class="bd-hero__signs" aria-hidden="true">
                <span class="nb-sign neon-sign nb-neon-pink flick only-nuit bd-sign bd-sign--kanji">漫画</span>
                <span class="nb-sign only-jour bd-sign bd-sign--kanji-day">漫画</span>
                <span class="nb-sign bd-sign bd-sign--strip"><span class="nb-screen-cyan"><span class="nb-dot blink"></span>BD · MANGA · WEBTOON</span></span>
            </div>
        </div>
    </section>

    {{-- ===== Filtres + grille ===== --}}
    <section class="nb-section pt-10" aria-labelledby="bd-grid-title">
        <div class="w-container">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <h2 id="bd-grid-title" class="nb-h2">{{ $heading }}</h2>
                    <p class="mt-2 text-sm text-[var(--muted)]">
                        {{ Fr::count($comics->total(), 'récit') }}
                        @if ($comics->lastPage() > 1)
                            · page {{ $comics->currentPage() }} sur {{ $comics->lastPage() }}
                        @endif
                    </p>
                </div>

                <nav class="nb-sort" aria-label="Trier les récits">
                    <span class="nb-sort__label">Trier</span>
                    @foreach ($sorts as $key => $label)
                        <a href="{{ route('comics.index', $params(['tri' => $key === 'recent' ? null : $key])) }}"
                            class="neo-btn neo-btn--ghost nb-sort__btn" @if ($sort === $key) aria-current="true" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="bd-filters mt-6">
                <form method="GET" action="{{ route('comics.index') }}" class="nb-filters" role="group" aria-label="Filtrer par type">
                    @if ($sort !== 'recent')
                        <input type="hidden" name="tri" value="{{ $sort }}">
                    @endif
                    @if ($genre)
                        <input type="hidden" name="genre" value="{{ $genre }}">
                    @endif
                    @foreach ($filters as $key => $label)
                        <button type="submit" name="type" value="{{ $key }}" class="neo-btn neo-btn--ghost nb-filter"
                            aria-pressed="{{ $filter === $key ? 'true' : 'false' }}">
                            {{ $label }} <small>{{ $counts[$key] ?? 0 }}</small>
                        </button>
                    @endforeach
                </form>

                @if ($genres->isNotEmpty())
                    <form method="GET" action="{{ route('comics.index') }}" class="bd-genre">
                        @if ($filter !== '')
                            <input type="hidden" name="type" value="{{ $filter }}">
                        @endif
                        @if ($sort !== 'recent')
                            <input type="hidden" name="tri" value="{{ $sort }}">
                        @endif
                        <label for="bd-genre" class="nb-sort__label">Genre</label>
                        <select id="bd-genre" name="genre" class="bd-select" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                            <option value="">Tous les genres</option>
                            @foreach ($genres as $slug => $label)
                                <option value="{{ $slug }}" @selected($genre === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="neo-btn neo-btn--ghost">Filtrer</button></noscript>
                    </form>
                @endif
            </div>

            <div class="nb-tiles bd-grid mt-10">
                @forelse ($comics as $comic)
                    @include('comics.partials.card', ['comic' => $comic])
                @empty
                    <p class="nb-empty" style="grid-column: 1 / -1;">
                        {{ $filter !== '' || $genre ? 'Aucun récit dessiné dans cette catégorie pour le moment.' : 'Aucun récit dessiné publié pour le moment.' }}
                    </p>
                @endforelse
            </div>

            {{ $comics->onEachSide(1)->links('pagination.afrikart') }}
        </div>
    </section>
</x-world-layout>
