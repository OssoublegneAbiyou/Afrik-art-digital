@php
    use App\Support\Fr;

    $followedComics = $followedComics ?? collect();
    $favoriteComics = $favoriteComics ?? collect();
    $favoriteAnimations = $favoriteAnimations ?? collect();
    $comicResume = $comicResume ?? [];
    $comicsEnabled = Route::has('comics.index') && \App\Http\Middleware\EnsureComicsEnabled::enabled();
    $sections = array_values(array_filter([
        ['id' => 'favoris', 'label' => 'Favoris', 'count' => $favorites->count() + $favoriteComics->count() + $favoriteAnimations->count()],
        ['id' => 'marque-pages', 'label' => 'Marque-pages', 'count' => $bookmarks->count()],
        $comicsEnabled ? ['id' => 'series-suivies', 'label' => 'Séries suivies', 'count' => $followedComics->count()] : null,
        ['id' => 'artistes-suivis', 'label' => 'Artistes suivis', 'count' => $followedArtists->count()],
    ]));
@endphp

<x-world-layout world="pont" title="Ma collection" description="Tes illustrations favorites, tes marque-pages et les artistes que tu suis." robots-noindex>
    <div class="ph pc">
        <div class="ph-halos w-deco" aria-hidden="true">
            <div class="pont-halo" style="width:480px;height:480px;top:-140px;right:-160px;background:rgba(255,46,136,.18)"></div>
            <div class="pont-halo" style="width:420px;height:420px;top:620px;left:-180px;background:rgba(46,242,255,.12)"></div>
        </div>

        <section class="w-container pc-hero" aria-labelledby="pc-title">
            <p class="ph-kicker">Mon espace</p>
            <h1 id="pc-title" class="ph-h2 mt-3">
                <span class="font-bebas">Ma collection</span>
                <span class="font-cormorant ph-h2__italic">tout ce que tu gardes</span>
            </h1>
            <p class="pc-lead">Les œuvres que tu as aimées, les récits que tu as commencés et les artistes que tu suis, réunis au même endroit.</p>

            <nav class="pc-tabs" aria-label="Sections de ma collection">
                @foreach ($sections as $section)
                    <a href="#{{ $section['id'] }}" class="pc-tab">
                        {{ $section['label'] }} <span class="pc-tab__count">{{ $section['count'] }}</span>
                    </a>
                @endforeach
            </nav>

            @if (session('success'))
                <p class="pc-flash" role="status">{{ session('success') }}</p>
            @endif
        </section>

        {{-- Favoris --}}
        <section id="favoris" class="w-container pc-section" aria-labelledby="pc-fav-title">
            <h2 id="pc-fav-title" class="pc-h2">
                <span class="font-bebas">Favoris</span>
                <span class="font-cormorant">{{ Fr::count($favorites->count(), 'illustration') }}</span>
            </h2>

            @if ($favorites->isEmpty() && $favoriteComics->isEmpty() && $favoriteAnimations->isEmpty())
                <p class="pc-empty">
                    Aucun favori pour l'instant. Sur la fiche d'une illustration, touche « Ajouter aux favoris » pour la retrouver ici.
                    <a href="{{ route('public.illustrations') }}">Parcourir la galerie →</a>
                </p>
            @elseif ($favorites->isNotEmpty())
                <ul class="pc-grid pc-grid--art" role="list">
                    @foreach ($favorites as $illustration)
                        @php($artistName = $illustration->artist?->user?->name ?? 'Artiste')
                        <li class="pc-card">
                            <a href="{{ route('public.illustration', $illustration) }}" class="pc-card__media">
                                <img src="{{ $illustration->thumbnailUrl() }}" alt="{{ $illustration->title }} par {{ $artistName }}" loading="lazy" decoding="async">
                            </a>
                            <div class="pc-card__body">
                                <a href="{{ route('public.illustration', $illustration) }}" class="pc-card__title">{{ $illustration->title }}</a>
                                @if ($illustration->artist)
                                    <a href="{{ route('public.artist', $illustration->artist) }}" class="pc-card__meta">{{ $artistName }}</a>
                                @endif
                                <form method="POST" action="{{ route('illustrations.unfavorite', $illustration) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pc-remove">Retirer des favoris</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($favoriteComics->isNotEmpty())
                <h3 class="pc-h2 mt-10" id="pc-fav-comics-title">
                    <span class="font-bebas">Récits dessinés</span>
                    <span class="font-cormorant">{{ Fr::count($favoriteComics->count(), 'récit') }}</span>
                </h3>
                <ul class="pc-grid pc-grid--books" role="list" aria-labelledby="pc-fav-comics-title">
                    @foreach ($favoriteComics as $comic)
                        @include('comics.partials.collection-card', ['comic' => $comic, 'resume' => null, 'favorite' => true])
                    @endforeach
                </ul>
            @endif

            @if ($favoriteAnimations->isNotEmpty())
                <h3 class="pc-h2 mt-10" id="pc-fav-anim-title">
                    <span class="font-bebas">Animations</span>
                    <span class="font-cormorant">{{ Fr::count($favoriteAnimations->count(), 'animation') }}</span>
                </h3>
                <ul class="pc-grid pc-grid--art" role="list" aria-labelledby="pc-fav-anim-title">
                    @foreach ($favoriteAnimations as $animation)
                        @php($authorName = $animation->user?->name ?? 'Artiste')
                        <li class="pc-card">
                            <a href="{{ route('animations.show', $animation) }}" class="pc-card__media">
                                @if ($animation->posterUrl() && ! $animation->contenu_sensible)
                                    <img src="{{ $animation->posterUrl() }}" alt="Image de « {{ $animation->titre }} » par {{ $authorName }}" loading="lazy" decoding="async">
                                @endif
                            </a>
                            <div class="pc-card__body">
                                <span class="pc-card__kicker">Animation · {{ $animation->modeLabel() }}{{ $animation->durationLabel() ? ' · '.$animation->durationLabel() : '' }}</span>
                                <a href="{{ route('animations.show', $animation) }}" class="pc-card__title">{{ $animation->titre }}</a>
                                @if ($animation->artist)
                                    <a href="{{ route('public.artist', $animation->artist) }}" class="pc-card__meta">{{ $authorName }}</a>
                                @endif
                                <form method="POST" action="{{ route('animations.favorite', $animation) }}">
                                    @csrf
                                    <button type="submit" class="pc-remove">Retirer des favoris</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Séries suivies (récits dessinés) --}}
        @if ($comicsEnabled)
            <section id="series-suivies" class="w-container pc-section" aria-labelledby="pc-series-title">
                <h2 id="pc-series-title" class="pc-h2">
                    <span class="font-bebas">Séries suivies</span>
                    <span class="font-cormorant">{{ Fr::count($followedComics->count(), 'récit dessiné', 'récits dessinés') }}</span>
                </h2>

                @if ($followedComics->isEmpty())
                    <p class="pc-empty">
                        Tu ne suis encore aucune série. Sur la fiche d'un récit dessiné, touche « Suivre la série » pour être prévenu des nouveaux chapitres.
                        <a href="{{ route('comics.index') }}">Voir les récits dessinés →</a>
                    </p>
                @else
                    <ul class="pc-grid pc-grid--books" role="list">
                        @foreach ($followedComics as $comic)
                            @include('comics.partials.collection-card', ['comic' => $comic, 'resume' => $comicResume[$comic->id] ?? null, 'favorite' => false])
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        {{-- Marque-pages --}}
        <section id="marque-pages" class="w-container pc-section" aria-labelledby="pc-book-title">
            <h2 id="pc-book-title" class="pc-h2">
                <span class="font-bebas">Marque-pages</span>
                <span class="font-cormorant">{{ Fr::count($bookmarks->count(), 'récit') }}</span>
            </h2>

            @if ($bookmarks->isEmpty())
                <p class="pc-empty">
                    Aucun marque-page pour l'instant. En lisant un récit, touche « Ajouter un marque-page » pour le reprendre plus tard.
                    <a href="{{ route('public.writers') }}">Découvrir les écrivains →</a>
                </p>
            @else
                <ul class="pc-grid pc-grid--books" role="list">
                    @foreach ($bookmarks as $document)
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
                                @if ($document->writer)
                                    <a href="{{ route('public.writer', $document->writer) }}" class="pc-card__meta">{{ $writerName }}</a>
                                @endif
                                @php($read = $progress->get($document->id))
                                @if ($read && $read->percent() > 0)
                                    <p class="pc-progress">
                                        <span class="pc-progress__bar" aria-hidden="true"><span style="width: {{ $read->percent() }}%"></span></span>
                                        {{ $read->percent() >= 97 ? 'Terminé' : 'Lu à '.$read->percent().' %' }}
                                    </p>
                                @endif
                                <a href="{{ route('documents.read', $document) }}" class="pont-btn pont-btn--ghost pc-card__cta">
                                    {{ $read && $read->percent() > 0 && $read->percent() < 97 ? 'Reprendre la lecture' : 'Lire' }}
                                </a>
                                <form method="POST" action="{{ route('documents.unbookmark', $document) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pc-remove">Retirer le marque-page</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Artistes suivis --}}
        <section id="artistes-suivis" class="w-container pc-section pb-20" aria-labelledby="pc-follow-title">
            <h2 id="pc-follow-title" class="pc-h2">
                <span class="font-bebas">Artistes suivis</span>
                <span class="font-cormorant">{{ Fr::count($followedArtists->count(), 'artiste') }}</span>
            </h2>

            @if ($followedArtists->isEmpty())
                <p class="pc-empty">
                    Tu ne suis encore aucun artiste. Sur la page d'un illustrateur, touche « Suivre » pour le retrouver ici.
                    <a href="{{ route('public.artists') }}">Voir les illustrateurs →</a>
                </p>
            @else
                <ul class="pc-grid pc-grid--artists" role="list">
                    @foreach ($followedArtists as $artist)
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
                            <form method="POST" action="{{ route('artists.unfollow', $artist) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pc-remove">Ne plus suivre</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-world-layout>
