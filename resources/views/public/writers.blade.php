@php
    $skies = \App\Support\SongeSky::all();
    $genreKeys = array_keys(\App\Models\Genre::options());
    // Emblèmes flottants (grand écran) : les cinq genres d'origine ont leur place dans la feuille de style,
    // les genres ajoutés dans l'admin prennent les emplacements suivants.
    $extraEmblemSpots = [[8, 62], [52, 4], [4, 44], [86, 14], [58, 88], [20, 82]];
    $placedEmblems = ['fantasy', 'science-fiction', 'thriller', 'conte', 'poesie'];
    $counts = collect($genreKeys)->mapWithKeys(fn ($g) => [$g => (int) ($genreCounts[$g] ?? 0)])->all();
    $counts['all'] = $documents->count();
    $initial = fn ($name) => mb_strtoupper(mb_substr(trim((string) $name) ?: '?', 0, 1));
    $featuredGenre = $featuredWriter ? ($mainGenres[$featuredWriter->id] ?? null) : null;
    $joinUrl = route('public.about').'#rejoindre';
@endphp

<x-world-layout
    world="songe"
    title="Écrivains"
    :description="'Toutes les plumes du collectif '.$collectif['nom'].' : romans, récits et textes à lire en ligne.'"
    overlay-header
>
    <x-slot name="head">
        <style>body { --hdr-ink-d: {{ $skies['all']['ink_day'] }}; --hdr-ink-n: {{ $skies['all']['ink_night'] }}; }</style>
        <script>
            document.addEventListener('alpine:init', function () {
                window.Alpine.data('songeWriters', function (skies, counts) {
                    return {
                        genre: 'all',
                        skies: skies,
                        counts: counts,
                        get sky() { return this.skies[this.genre] || this.skies.all; },
                        get skyStyle() {
                            var s = this.sky;
                            return '--ink-d:' + s.ink_day + ';--ink-n:' + s.ink_night + ';--glow:' + s.glow
                                + ';--on-ink-d:' + s.on_ink_day + ';--on-ink-n:' + s.on_ink_night + ';';
                        },
                        get visibleCount() { return this.counts[this.genre] || 0; },
                        get visibleLabel() { var n = this.visibleCount; return n + (n >= 2 ? ' œuvres' : ' œuvre'); },
                        select(g) { this.genre = g; },
                        toggle(g) { this.genre = this.genre === g ? 'all' : g; },
                        matches(g) { return this.genre === 'all' || this.genre === g; },
                        init() {
                            var paint = () => {
                                document.body.style.setProperty('--hdr-ink-d', this.sky.ink_day);
                                document.body.style.setProperty('--hdr-ink-n', this.sky.ink_night);
                            };
                            this.$watch('genre', paint);
                            paint();
                        },
                    };
                });
            });
        </script>
    </x-slot>

    <div x-data="songeWriters(@js($skies), @js($counts))">
        {{-- 1. Ciel des genres --}}
        <section class="sg-hero sg-tone" style="{{ \App\Support\SongeSky::style('all') }}" :style="skyStyle" :class="'is-' + genre" aria-labelledby="ciel-titre">
            <div class="sg-hero__layers w-deco" aria-hidden="true">
                @foreach ($skies as $key => $sky)
                    <div class="sg-hero__layer sg-tone {{ $key === 'all' ? 'is-on' : '' }}" style="{{ \App\Support\SongeSky::style($key) }}" :class="{ 'is-on': genre === @js($key) }"></div>
                @endforeach

                {{-- Jour : nuages --}}
                <div class="only-jour">
                    <span class="cloud drift sg-cloud" style="width:180px;height:46px;top:22%;left:6%;"></span>
                    <span class="cloud drift sg-cloud" style="width:240px;height:56px;top:70%;left:38%;animation-delay:-3s;"></span>
                    <span class="cloud drift sg-cloud" style="width:150px;height:40px;top:16%;right:30%;animation-delay:-5s;"></span>
                </div>

                {{-- Nuit : étoiles, croissant, étoiles filantes --}}
                <div class="only-nuit">
                    <div class="starfield"></div>
                    <span class="star" style="width:4px;height:4px;top:18%;left:44%;"></span>
                    <span class="star" style="width:3px;height:3px;top:34%;left:12%;animation-delay:-1s;"></span>
                    <span class="star" style="width:5px;height:5px;top:58%;left:62%;animation-delay:-2s;"></span>
                    <span class="star" style="width:3px;height:3px;top:76%;left:24%;animation-delay:-.5s;"></span>
                    <span class="star" style="width:4px;height:4px;top:12%;left:72%;animation-delay:-1.6s;"></span>
                    <span class="crescent sg-hero__moon drift"></span>
                    <span class="shooting-star" style="top:14%;left:8%;"></span>
                    <span class="shooting-star" style="top:30%;left:46%;animation-delay:-3.5s;"></span>
                </div>

                {{-- Pétales : uniquement en Poésie --}}
                <div class="sg-petals" x-show="genre === 'poesie'" x-transition.opacity.duration.600ms x-cloak>
                    @foreach ([[8, 0], [18, -2], [27, -4.5], [39, -1], [51, -6], [60, -3], [71, -5], [80, -.5], [88, -2.5], [95, -4]] as [$left, $delay])
                        <span class="petal" style="left:{{ $left }}%;animation-delay:{{ $delay }}s;"></span>
                    @endforeach
                </div>
            </div>

            <div class="w-container sg-hero__inner">
                <div class="sg-hero__text">
                    <p class="sg-eyebrow">Le ciel des écrivains</p>
                    <h1 id="ciel-titre" class="sg-hero__title">
                        <span class="only-jour">Chaque genre est<br><em>un ciel différent.</em></span>
                        <span class="only-nuit">La nuit, chaque genre<br><em>devient une constellation.</em></span>
                    </h1>
                    <p class="sg-hero__tagline" aria-live="polite" x-text="sky.tagline">{{ $skies['all']['tagline'] }}</p>

                    <div class="sg-hero__pills" role="group" aria-label="Filtrer les œuvres par genre">
                        @foreach ($skies as $key => $sky)
                            <button type="button" class="songe-pill" aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}" :aria-pressed="(genre === @js($key)).toString()" @click="select(@js($key))">
                                <span>{{ $sky['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="sg-emblems w-deco" aria-hidden="true">
                    @php($extraIndex = 0)
                    @foreach ($genreKeys as $key)
                        @php($spot = in_array($key, $placedEmblems, true) ? null : ($extraEmblemSpots[$extraIndex++ % count($extraEmblemSpots)]))
                        <span class="sg-emblem sg-emblem--{{ $key }}" style="--glow: {{ $skies[$key]['glow'] }};{{ $spot ? ' top: '.$spot[0].'%; left: '.$spot[1].'%;' : '' }}" :class="{ 'is-active': genre === @js($key), 'is-dim': genre !== 'all' && genre !== @js($key) }">
                            <x-genre-emblem :genre="$key" class="drift sg-emblem__svg" style="animation-delay: -{{ $loop->index * 1.6 }}s" />
                        </span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 2. Plume du jour --}}
        <div class="w-container sg-featured-wrap">
            @if ($featuredWriter)
                <article class="songe-card sg-featured" aria-labelledby="plume-du-jour">
                    <span class="sg-avatar sg-avatar--xl sg-tone" style="{{ \App\Support\SongeSky::style($featuredGenre) }}" aria-hidden="true">{{ $initial($featuredWriter->user?->name) }}</span>
                    <div class="sg-featured__body">
                        <p id="plume-du-jour" class="sg-eyebrow sg-eyebrow--acc">Plume du jour</p>
                        <h2 class="sg-featured__name">{{ $featuredWriter->user?->name ?? 'Écrivain' }}</h2>
                        @if (filled($featuredWriter->bio))
                            <blockquote class="sg-featured__quote">« {{ \Illuminate\Support\Str::limit(trim($featuredWriter->bio), 220) }} »</blockquote>
                        @endif
                        <p class="sg-featured__meta">
                            {{ \App\Support\Fr::count($featuredWriter->documents->count(), 'œuvre') }}
                            @if ($featuredGenre)
                                · ciel {{ mb_strtolower(\App\Models\Genre::labelFor($featuredGenre)) }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('public.writer', $featuredWriter) }}" class="songe-btn sg-featured__cta">Visiter son univers <span aria-hidden="true">→</span></a>
                </article>
            @endif
        </div>

        {{-- 3. Les royaumes du songe --}}
        <section class="w-container sg-section" aria-labelledby="royaumes-titre">
            <header class="sg-section__head">
                <p class="sg-eyebrow sg-eyebrow--acc">{{ \App\Support\SongeSky::countLabel(count($genreKeys)) }}</p>
                <h2 id="royaumes-titre" class="sg-section__title">Les royaumes <em>du songe</em></h2>
                <p class="sg-section__lead">Choisis un royaume pour ne voir que ses histoires sur les étagères. Un second clic les montre toutes.</p>
            </header>

            <div class="sg-realms">
                @foreach ($genreKeys as $key)
                    <button type="button" class="sg-realm sg-tone sg-sky" style="{{ \App\Support\SongeSky::style($key) }}"
                        aria-pressed="false" :aria-pressed="(genre === @js($key)).toString()" @click="toggle(@js($key))"
                        aria-label="{{ $skies[$key]['label'] }} : {{ $counts[$key] > 0 ? \App\Support\Fr::count($counts[$key], 'œuvre') : 'à écrire' }}">
                        <x-genre-emblem :genre="$key" class="sg-realm__emblem" />
                        <span class="sg-realm__name">{{ $skies[$key]['label'] }}</span>
                        @if ($skies[$key]['description'])
                            <span class="sg-realm__desc">{{ $skies[$key]['description'] }}</span>
                        @endif
                        <span class="sg-realm__count">{{ $counts[$key] > 0 ? \App\Support\Fr::count($counts[$key], 'œuvre') : 'À ÉCRIRE' }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- 4. Les plumes --}}
        <section class="w-container sg-section" aria-labelledby="plumes-titre">
            <header class="sg-section__head">
                <p class="sg-eyebrow sg-eyebrow--acc">Le collectif</p>
                <h2 id="plumes-titre" class="sg-section__title">Les <em>plumes</em></h2>
            </header>

            <ul class="sg-writers" role="list">
                @foreach ($writers as $writer)
                    @php($writerGenre = $mainGenres[$writer->id] ?? null)
                    <li>
                        <a href="{{ route('public.writer', $writer) }}" class="songe-card sg-writer">
                            <span class="sg-avatar sg-tone" style="{{ \App\Support\SongeSky::style($writerGenre) }}" aria-hidden="true">{{ $initial($writer->user?->name) }}</span>
                            <span class="sg-writer__body">
                                <span class="sg-writer__name">{{ $writer->user?->name ?? 'Écrivain' }}</span>
                                @if ($writerGenre)
                                    <span class="sg-writer__genre">
                                        <x-genre-emblem :genre="$writerGenre" class="h-4 w-4" />
                                        {{ \App\Models\Genre::labelFor($writerGenre) }}
                                    </span>
                                @endif
                                @if (filled($writer->bio))
                                    <span class="sg-writer__bio">{{ \Illuminate\Support\Str::limit(trim($writer->bio), 120) }}</span>
                                @endif
                                <span class="sg-writer__count">{{ \App\Support\Fr::count($writer->documents->count(), 'œuvre') }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ $joinUrl }}" class="sg-writer sg-writer--free">
                        <span class="sg-avatar sg-avatar--free" aria-hidden="true">+</span>
                        <span class="sg-writer__body">
                            <span class="sg-writer__name">Une place libre dans le ciel.</span>
                            <span class="sg-writer__join">Rejoindre le collectif <span aria-hidden="true">→</span></span>
                        </span>
                    </a>
                </li>
            </ul>
        </section>

        {{-- 5. Sur les étagères --}}
        <section class="w-container sg-section sg-section--last" aria-labelledby="etageres-titre">
            <header class="sg-section__head sg-section__head--row">
                <div>
                    <p class="sg-eyebrow sg-eyebrow--acc">La bibliothèque</p>
                    <h2 id="etageres-titre" class="sg-section__title">Sur les <em>étagères</em></h2>
                </div>
                <p class="sg-count" aria-live="polite">
                    <span x-text="visibleLabel">{{ \App\Support\Fr::count($documents->count(), 'œuvre') }}</span>
                    <span x-show="genre !== 'all'" x-cloak>· <span x-text="sky.label"></span></span>
                </p>
            </header>

            @if ($documents->isEmpty())
                <p class="songe-card sg-empty">Aucune œuvre publiée pour le moment. Tu écris ? <a href="{{ $joinUrl }}">Rejoins le collectif</a> et publie la première ici.</p>
            @else
                <ul class="sg-shelf" role="list">
                    @foreach ($documents as $document)
                        <li x-show="matches(@js($document->genre))">
                            @include('public.songe.book', ['document' => $document])
                        </li>
                    @endforeach
                </ul>
                <p class="songe-card sg-empty" x-show="genre !== 'all' && visibleCount === 0" x-cloak>
                    Ce ciel attend sa première histoire. Tu écris dans ce genre ?
                    <a href="{{ $joinUrl }}">Rejoins le collectif</a> et publie-la ici.
                </p>
            @endif

            <p class="sg-section__more">
                <a href="{{ route('public.literary-works') }}" class="songe-btn songe-btn--ghost">Toutes les œuvres littéraires</a>
            </p>
        </section>
    </div>
</x-world-layout>
