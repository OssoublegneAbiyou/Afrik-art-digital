{{--
    « Ma collection » (univers Pont) : un récit dessiné suivi ou favori.
    Variables : $comic, $resume (ComicController::resumePoint() ou null), $favorite (bool : bouton « Retirer des favoris »).
--}}
@php
    $authorName = $comic->user?->name ?? 'Artiste';
    $firstChapter = $comic->relationLoaded('chapters') ? $comic->chapters->first() : null;
    $readUrl = $resume
        ? route('comics.read', [$comic, $resume['chapter']->routeNumber(), 'page' => $resume['page']])
        : ($firstChapter ? route('comics.read', [$comic, $firstChapter->routeNumber()]) : route('comics.show', $comic));
@endphp
<li class="pc-card pc-card--book">
    <a href="{{ route('comics.show', $comic) }}" class="pc-card__media">
        @if ($comic->coverUrl())
            <img src="{{ $comic->coverUrl() }}" alt="Couverture de « {{ $comic->titre }} » par {{ $authorName }}" loading="lazy" decoding="async">
        @endif
    </a>
    <div class="pc-card__body">
        <span class="pc-card__kicker">
            {{ $comic->readingKind() }}{{ $comic->isOneShot() ? '' : ' · '.\App\Support\Fr::count((int) $comic->chapters_count, 'chapitre') }}{{ $comic->hasNewChapter() ? ' · Nouveau' : '' }}
        </span>
        <a href="{{ route('comics.show', $comic) }}" class="pc-card__title">{{ $comic->titre }}</a>
        @if ($comic->artist)
            <a href="{{ route('public.artist', $comic->artist) }}" class="pc-card__meta">{{ $authorName }}</a>
        @else
            <span class="pc-card__meta">{{ $authorName }}</span>
        @endif
        @unless ($favorite)
            <a href="{{ $readUrl }}" class="pont-btn pont-btn--ghost pc-card__cta">{{ $resume['label'] ?? 'Commencer la lecture' }}</a>
        @endunless
        @if ($favorite)
            <form method="POST" action="{{ route('comics.favorite', $comic) }}">
                @csrf
                <button type="submit" class="pc-remove">Retirer des favoris</button>
            </form>
        @else
            <form method="POST" action="{{ route('comics.follow', $comic) }}">
                @csrf
                <button type="submit" class="pc-remove">Ne plus suivre</button>
            </form>
        @endif
    </div>
</li>
