@php
    use App\Support\Fr;

    $sections = $results ? [
        ['id' => 'oeuvres', 'label' => 'Illustrations', 'count' => $results['illustrationsTotal']],
        ['id' => 'textes', 'label' => 'Textes', 'count' => $results['documentsTotal']],
        ['id' => 'recits-dessines', 'label' => 'Récits dessinés', 'count' => $results['comicsTotal'] ?? 0],
        ['id' => 'illustrateurs', 'label' => 'Illustrateurs', 'count' => $results['artists']->count()],
        ['id' => 'ecrivains', 'label' => 'Écrivains', 'count' => $results['writers']->count()],
    ] : [];
    $more = fn (int $total, int $shown) => $total > $shown ? 'Les '.$shown.' plus récents sur '.$total.' : précise ta recherche pour affiner.' : null;
@endphp

<x-world-layout world="pont" :title="$query !== '' ? 'Recherche : '.$query : 'Recherche'" description="Cherche une illustration, un texte, un illustrateur ou un écrivain du collectif." robots-noindex>
    <div class="ph pc">
        <div class="ph-halos w-deco" aria-hidden="true">
            <div class="pont-halo" style="width:480px;height:480px;top:-140px;right:-160px;background:rgba(46,242,255,.14)"></div>
            <div class="pont-halo" style="width:420px;height:420px;top:620px;left:-180px;background:rgba(255,46,136,.16)"></div>
        </div>

        <section class="w-container pc-hero" aria-labelledby="ps-title">
            <h1 id="ps-title" class="ph-h2">
                <span class="font-bebas">Recherche</span>
                <span class="font-cormorant ph-h2__italic">dans tout le collectif</span>
            </h1>

            <form method="GET" action="{{ route('search') }}" class="ps-form" role="search">
                <label for="ps-q" class="sr-only">Rechercher une œuvre, un texte, un illustrateur ou un écrivain</label>
                <input id="ps-q" type="search" name="q" value="{{ $query }}" minlength="2" maxlength="100" required
                    placeholder="Une œuvre, un texte, un artiste, un auteur…" autocomplete="off" @if ($query === '') autofocus @endif>
                <button type="submit" class="pont-btn pont-btn--rose">Chercher</button>
            </form>

            @if ($results)
                <p class="pc-lead" role="status">
                    @if ($results['total'] === 0)
                        Aucun résultat pour « {{ $query }} ». Essaie un autre mot, un nom d'artiste ou un titre plus court.
                    @else
                        {{ Fr::count($results['total'], 'résultat') }} pour « {{ $query }} ».
                    @endif
                </p>

                @if ($results['total'] > 0)
                    <nav class="pc-tabs" aria-label="Types de résultats">
                        @foreach ($sections as $section)
                            @if ($section['count'] > 0)
                                <a href="#{{ $section['id'] }}" class="pc-tab">{{ $section['label'] }} <span class="pc-tab__count">{{ $section['count'] }}</span></a>
                            @endif
                        @endforeach
                    </nav>
                @endif
            @elseif ($query !== '')
                <p class="pc-lead" role="status">Tape au moins deux caractères.</p>
            @else
                <p class="pc-lead">Titres, résumés et noms : la recherche parcourt les illustrations, les textes, les illustrateurs et les écrivains.</p>
            @endif
        </section>

        @if ($results && $results['total'] > 0)
            @if ($results['illustrations']->isNotEmpty())
                <section id="oeuvres" class="w-container pc-section" aria-labelledby="ps-ill">
                    <h2 id="ps-ill" class="pc-h2"><span class="font-bebas">Illustrations</span> <span class="font-cormorant">{{ Fr::count($results['illustrationsTotal'], 'œuvre') }}</span></h2>
                    @if ($note = $more($results['illustrationsTotal'], $results['illustrations']->count()))
                        <p class="ps-note">{{ $note }}</p>
                    @endif
                    <ul class="pc-grid pc-grid--art" role="list">
                        @foreach ($results['illustrations'] as $illustration)
                            @php($artistName = $illustration->artist?->user?->name ?? 'Artiste')
                            <li class="pc-card">
                                <a href="{{ route('public.illustration', $illustration) }}" class="pc-card__media">
                                    <img src="{{ $illustration->thumbnailUrl() }}" alt="{{ $illustration->title }} par {{ $artistName }}" loading="lazy" decoding="async">
                                </a>
                                <div class="pc-card__body">
                                    @if ($illustration->typeLabel())
                                        <span class="pc-card__kicker">{{ $illustration->typeLabel() }}</span>
                                    @endif
                                    <a href="{{ route('public.illustration', $illustration) }}" class="pc-card__title">{{ $illustration->title }}</a>
                                    <a href="{{ route('public.artist', $illustration->artist) }}" class="pc-card__meta">{{ $artistName }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($results['documents']->isNotEmpty())
                <section id="textes" class="w-container pc-section" aria-labelledby="ps-doc">
                    <h2 id="ps-doc" class="pc-h2"><span class="font-bebas">Textes</span> <span class="font-cormorant">{{ Fr::count($results['documentsTotal'], 'récit') }}</span></h2>
                    @if ($note = $more($results['documentsTotal'], $results['documents']->count()))
                        <p class="ps-note">{{ $note }}</p>
                    @endif
                    <ul class="pc-grid pc-grid--books" role="list">
                        @foreach ($results['documents'] as $document)
                            @php($writerName = $document->writer?->user?->name ?? 'Auteur')
                            <li class="pc-card pc-card--book">
                                <a href="{{ route('documents.read', $document) }}" class="pc-card__media">
                                    <img src="{{ $document->coverThumbnailUrl() }}" alt="Couverture de « {{ $document->title }} »" loading="lazy" decoding="async">
                                </a>
                                <div class="pc-card__body">
                                    @if ($document->genreLabel())
                                        <span class="pc-card__kicker">{{ $document->genreLabel() }}</span>
                                    @endif
                                    <a href="{{ route('documents.read', $document) }}" class="pc-card__title">{{ $document->title }}</a>
                                    <a href="{{ route('public.writer', $document->writer) }}" class="pc-card__meta">{{ $writerName }}</a>
                                    @if ($document->description)
                                        <p class="ps-excerpt">{{ \Illuminate\Support\Str::limit($document->description, 140) }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (($results['comics'] ?? collect())->isNotEmpty())
                <section id="recits-dessines" class="w-container pc-section" aria-labelledby="ps-bd">
                    <h2 id="ps-bd" class="pc-h2"><span class="font-bebas">Récits dessinés</span> <span class="font-cormorant">{{ Fr::count($results['comicsTotal'], 'récit') }}</span></h2>
                    @if ($note = $more($results['comicsTotal'], $results['comics']->count()))
                        <p class="ps-note">{{ $note }}</p>
                    @endif
                    <ul class="pc-grid pc-grid--books" role="list">
                        @foreach ($results['comics'] as $comic)
                            @php($authorName = $comic->user?->name ?? 'Artiste')
                            <li class="pc-card pc-card--book">
                                <a href="{{ route('comics.show', $comic) }}" class="pc-card__media">
                                    @if ($comic->coverUrl())
                                        <img src="{{ $comic->coverUrl() }}" alt="Couverture de « {{ $comic->titre }} » par {{ $authorName }}" loading="lazy" decoding="async">
                                    @endif
                                </a>
                                <div class="pc-card__body">
                                    <span class="pc-card__kicker">{{ $comic->typeLabel() }} · {{ $comic->readingKind() }}</span>
                                    <a href="{{ route('comics.show', $comic) }}" class="pc-card__title">{{ $comic->titre }}</a>
                                    @if ($comic->artist)
                                        <a href="{{ route('public.artist', $comic->artist) }}" class="pc-card__meta">{{ $authorName }}</a>
                                    @else
                                        <span class="pc-card__meta">{{ $authorName }}</span>
                                    @endif
                                    @if ($comic->resume)
                                        <p class="ps-excerpt">{{ \Illuminate\Support\Str::limit($comic->resume, 140) }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($results['artists']->isNotEmpty())
                <section id="illustrateurs" class="w-container pc-section" aria-labelledby="ps-art">
                    <h2 id="ps-art" class="pc-h2"><span class="font-bebas">Illustrateurs</span> <span class="font-cormorant">{{ Fr::count($results['artists']->count(), 'studio') }}</span></h2>
                    <ul class="pc-grid pc-grid--artists" role="list">
                        @foreach ($results['artists'] as $artist)
                            @php($name = $artist->user?->name ?? 'Artiste')
                            <li class="pc-artist glass-card">
                                <a href="{{ route('public.artist', $artist) }}" class="pc-artist__link">
                                    <span class="pc-artist__thumbs" aria-hidden="true">
                                        @forelse ($artist->illustrations->sortByDesc('created_at')->take(3) as $illustration)
                                            <img src="{{ $illustration->thumbnailUrl() }}" alt="" loading="lazy" decoding="async">
                                        @empty
                                            <span class="pc-artist__initial font-bebas">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
                                        @endforelse
                                    </span>
                                    <span class="pc-artist__name font-bebas">{{ $name }}</span>
                                    <span class="pc-card__meta">{{ Fr::count($artist->illustrations->count(), 'illustration') }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($results['writers']->isNotEmpty())
                <section id="ecrivains" class="w-container pc-section pb-20" aria-labelledby="ps-wri">
                    <h2 id="ps-wri" class="pc-h2"><span class="font-bebas">Écrivains</span> <span class="font-cormorant">{{ Fr::count($results['writers']->count(), 'plume') }}</span></h2>
                    <ul class="pc-grid pc-grid--artists" role="list">
                        @foreach ($results['writers'] as $writer)
                            @php($name = $writer->user?->name ?? 'Auteur')
                            <li class="pc-artist glass-card">
                                <a href="{{ route('public.writer', $writer) }}" class="pc-artist__link">
                                    <span class="pc-artist__name font-cormorant ps-writer-name">{{ $name }}</span>
                                    <span class="pc-card__meta">{{ Fr::count($writer->documents->count(), 'œuvre') }}</span>
                                    @if ($writer->bio)
                                        <span class="ps-excerpt">{{ \Illuminate\Support\Str::limit($writer->bio, 120) }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endif
        <div class="pb-16" aria-hidden="true"></div>
    </div>
</x-world-layout>
