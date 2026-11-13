{{--
    Carte d'un récit dessiné (univers Néo) : couverture 2:3, type, badges « Nouveau chapitre » et « Contenu sensible ».
    Variables : $comic (avec chapters_count / likes_count si chargés), $showAuthor (true par défaut), $headingLevel (3).
--}}
@php
    $showAuthor = $showAuthor ?? true;
    $headingLevel = $headingLevel ?? 3;
    $authorName = $comic->user?->name ?? 'Artiste';
    $chapterCount = (int) ($comic->chapters_count ?? 0);
    $meta = array_filter([
        $comic->readingKind(),
        $comic->genreLabel(),
        ! $comic->isOneShot() && $chapterCount > 0 ? \App\Support\Fr::count($chapterCount, 'chapitre') : null,
        $comic->seriesStatusLabel() === 'Terminée' ? 'Terminée' : null,
    ]);
@endphp

<article class="nb-tile {{ $comic->isOneShot() ? 'nb-tile--a2' : 'nb-tile--a1' }} bd-card">
    <a href="{{ route('comics.show', $comic) }}" class="nb-tile__frame tilt bd-card__frame">
        @if ($comic->coverUrl())
            <img src="{{ $comic->coverUrl() }}" alt="Couverture de « {{ $comic->titre }} » par {{ $authorName }}" class="bd-card__cover" width="400" height="600" loading="lazy" decoding="async">
        @else
            <span class="bd-card__placeholder" aria-hidden="true">漫画</span>
            <span class="sr-only">{{ $comic->titre }}</span>
        @endif
        <span class="nb-tile__type">{{ $comic->typeLabel() }}</span>
        @if ($comic->hasNewChapter() || $comic->contenu_sensible)
            <span class="bd-card__badges">
                @if ($comic->hasNewChapter())
                    <span class="bd-badge bd-badge--new">{{ $comic->isOneShot() ? 'NOUVEAU' : 'NOUVEAU CHAPITRE' }}</span>
                @endif
                @if ($comic->contenu_sensible)
                    <span class="bd-badge bd-badge--sensitive">Contenu sensible</span>
                @endif
            </span>
        @endif
    </a>
    <div class="nb-tile__body">
        <h{{ $headingLevel }}>
            <a href="{{ route('comics.show', $comic) }}" class="nb-tile__title">{{ $comic->titre }}</a>
        </h{{ $headingLevel }}>
        @if ($showAuthor)
            @if ($comic->artist)
                <a href="{{ route('public.artist', $comic->artist) }}" class="nb-tile__artist">{{ $authorName }}</a>
            @else
                <span class="nb-tile__artist">{{ $authorName }}</span>
            @endif
        @endif
        @if ($meta)
            <p class="bd-card__meta">{{ implode(' · ', $meta) }}</p>
        @endif
        @if ((int) ($comic->likes_count ?? 0) > 0)
            <p class="likes-badge nb-tile__likes">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2Z"/></svg>
                {{ $comic->likes_count }} <span class="sr-only">{{ (int) $comic->likes_count >= 2 ? 'personnes aiment' : 'personne aime' }}</span>
            </p>
        @endif
    </div>
</article>
