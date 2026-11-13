@php
    $genreLabels = \App\Models\Genre::options();
    $counts = collect($genreLabels)->map(fn ($label, $g) => $documents->where('genre', $g)->count())->all();
    $counts['all'] = $documents->count();
    $sky = \App\Support\SongeSky::get('all');
    $joinUrl = route('public.about').'#rejoindre';
@endphp

<x-world-layout
    world="songe"
    title="Œuvres littéraires"
    :description="'Romans, nouvelles et récits des écrivains du collectif '.$collectif['nom'].', à lire en ligne.'"
    overlay-header
>
    <x-slot name="head">
        <style>body { --hdr-ink-d: {{ $sky['ink_day'] }}; --hdr-ink-n: {{ $sky['ink_night'] }}; }</style>
    </x-slot>

    <div x-data="{
        genre: 'all',
        counts: @js($counts),
        matches(g) { return this.genre === 'all' || this.genre === g; },
        get visibleCount() { return this.counts[this.genre] || 0; },
        get visibleLabel() { var n = this.visibleCount; return n + (n >= 2 ? ' œuvres' : ' œuvre'); },
    }">
        <section class="sg-hero sg-hero--small sg-tone sg-sky" style="{{ \App\Support\SongeSky::style('all') }}" aria-labelledby="oeuvres-titre">
            <div class="sg-hero__layers w-deco" aria-hidden="true">
                <div class="only-jour">
                    <span class="cloud drift sg-cloud" style="width:200px;height:50px;top:30%;right:12%;"></span>
                    <span class="cloud drift sg-cloud" style="width:140px;height:36px;top:62%;right:38%;animation-delay:-4s;"></span>
                </div>
                <div class="only-nuit">
                    <div class="starfield"></div>
                    <span class="crescent sg-hero__moon drift"></span>
                    <span class="shooting-star" style="top:20%;left:30%;"></span>
                </div>
            </div>
            <div class="w-container sg-hero__inner">
                <div class="sg-hero__text">
                    <p class="sg-eyebrow">La bibliothèque du collectif</p>
                    <h1 id="oeuvres-titre" class="sg-hero__title">Toutes les œuvres<br><em>littéraires</em></h1>
                    <p class="sg-hero__tagline">Romans, contes, poèmes et récits : choisis un ciel et ouvre un livre.</p>
                </div>
            </div>
        </section>

        <section class="w-container sg-section sg-section--last" aria-labelledby="etageres-titre">
            <header class="sg-section__head sg-section__head--row">
                <div>
                    <p class="sg-eyebrow sg-eyebrow--acc">Bibliothèque</p>
                    <h2 id="etageres-titre" class="sg-section__title">Sur les <em>étagères</em></h2>
                </div>
                <p class="sg-count" aria-live="polite"><span x-text="visibleLabel">{{ \App\Support\Fr::count($documents->count(), 'œuvre') }}</span></p>
            </header>

            <div class="sg-filters" role="group" aria-label="Filtrer les œuvres par genre">
                <button type="button" class="songe-pill" aria-pressed="true" :aria-pressed="(genre === 'all').toString()" @click="genre = 'all'"><span>Tous</span></button>
                @foreach ($genreLabels as $key => $label)
                    <button type="button" class="songe-pill" aria-pressed="false" :aria-pressed="(genre === @js($key)).toString()" @click="genre = @js($key)">
                        <span><x-genre-emblem :genre="$key" class="inline h-5 w-5 align-[-3px]" /> {{ $label }}</span>
                    </button>
                @endforeach
            </div>

            @if ($documents->isEmpty())
                <p class="songe-card sg-empty mt-8">Aucune œuvre littéraire publiée pour le moment.</p>
            @else
                <ul class="sg-shelf mt-10" role="list">
                    @foreach ($documents as $document)
                        <li x-show="matches(@js($document->genre))">
                            @include('public.songe.book', ['document' => $document])
                        </li>
                    @endforeach
                </ul>
                <p class="songe-card sg-empty mt-8" x-show="genre !== 'all' && visibleCount === 0" x-cloak>
                    Ce ciel attend sa première histoire. Tu écris dans ce genre ?
                    <a href="{{ $joinUrl }}">Rejoins le collectif</a> et publie-la ici.
                </p>
            @endif

            <p class="sg-section__more">
                <a href="{{ route('public.writers') }}" class="songe-btn songe-btn--ghost">Découvrir les plumes</a>
            </p>
        </section>
    </div>
</x-world-layout>
