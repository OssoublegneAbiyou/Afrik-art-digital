{{--
    Carte d'une animation (univers Néo) : poster, mode (boucle / clip), durée, badge « Contenu sensible ».
    Une boucle tourne (muette) quand la carte est à l'écran et s'arrête hors écran (animation-player.js) ;
    avec « réduire les animations », poster seul et lecture au survol / focus. Un clip montre son poster et un
    symbole lecture. Contenu sensible : poster flouté, jamais de lecture dans la grille.
    Variables : $animation (likes_count si chargé), $showAuthor (true), $headingLevel (3).
--}}
@php
    $showAuthor = $showAuthor ?? true;
    $headingLevel = $headingLevel ?? 3;
    $authorName = $animation->user?->name ?? 'Artiste';
    $poster = $animation->posterUrl();
    $playsInGrid = $animation->isLoop() && ! $animation->contenu_sensible;
    $meta = array_filter([$animation->modeLabel(), $animation->genreLabel(), $animation->durationLabel()]);
@endphp

@if ($playsInGrid)
    @include('animations.partials.assets')
@endif

<article class="nb-tile {{ $animation->isLoop() ? 'nb-tile--a2' : 'nb-tile--a3' }} an-card">
    <a href="{{ route('animations.show', $animation) }}" class="nb-tile__frame tilt an-card__frame {{ $animation->contenu_sensible ? 'is-sensitive' : '' }}" data-animation-card-frame>
        @if ($poster)
            <img src="{{ $poster }}" alt="{{ $animation->contenu_sensible ? '' : 'Image de « '.$animation->titre.' » par '.$authorName }}" class="an-card__poster" loading="lazy" decoding="async">
        @else
            <span class="an-card__placeholder" aria-hidden="true">動画</span>
        @endif
        @if ($playsInGrid)
            <video class="an-card__video" data-animation-card muted loop playsinline preload="none"
                @if ($poster) poster="{{ $poster }}" @endif aria-hidden="true" tabindex="-1" disablepictureinpicture>
                @if ($animation->webmUrl())
                    <source src="{{ $animation->webmUrl() }}" type="video/webm; codecs=vp9">
                @endif
                <source src="{{ $animation->mp4Url() }}" type="video/mp4">
            </video>
        @endif
        @if (! $poster || $animation->contenu_sensible)
            <span class="sr-only">{{ $animation->titre }}</span>
        @endif
        <span class="nb-tile__type">Animé · {{ $animation->modeLabel() }}</span>
        @unless ($animation->isLoop())
            <span class="an-card__play" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="M8 5.5v13l10.5-6.5L8 5.5Z" fill="currentColor"/></svg>
            </span>
        @endunless
        <span class="an-card__badges">
            @if ($animation->durationLabel())
                <span class="an-badge">{{ $animation->durationLabel() }}</span>
            @endif
            @if ($animation->contenu_sensible)
                <span class="bd-badge bd-badge--sensitive">Contenu sensible</span>
            @endif
        </span>
    </a>
    <div class="nb-tile__body">
        <h{{ $headingLevel }}>
            <a href="{{ route('animations.show', $animation) }}" class="nb-tile__title">{{ $animation->titre }}</a>
        </h{{ $headingLevel }}>
        @if ($showAuthor)
            @if ($animation->artist)
                <a href="{{ route('public.artist', $animation->artist) }}" class="nb-tile__artist">{{ $authorName }}</a>
            @else
                <span class="nb-tile__artist">{{ $authorName }}</span>
            @endif
        @endif
        @if ($meta)
            <p class="bd-card__meta">{{ implode(' · ', $meta) }}</p>
        @endif
        @if ((int) ($animation->likes_count ?? 0) > 0)
            <p class="likes-badge nb-tile__likes">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2Z"/></svg>
                {{ $animation->likes_count }} <span class="sr-only">{{ (int) $animation->likes_count >= 2 ? 'personnes aiment' : 'personne aime' }}</span>
            </p>
        @endif
    </div>
</article>
