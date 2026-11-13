@props([
    'animation',
    // URL de comptage des vues (route animations.view) : seulement sur la fiche d'une animation visible.
    'viewUrl' => null,
])

{{--
    Lecteur d'animation (resources/js/animation-player.js, sans React).
    - Boucle : <video autoplay muted loop playsinline preload="metadata" poster>, lue seulement quand elle est à
      l'écran (pause sinon), jamais avec « réduire les animations » (poster + bouton lecture), bouton pause.
    - Clip : poster + gros bouton lecture central, jamais d'autoplay ; contrôles personnalisés aux couleurs Néo
      (lecture/pause, barre de temps, volume/muet, plein écran) ; raccourcis Espace, ← →, M, F.
    Sans JavaScript : la boucle tourne seule, le clip garde les contrôles du navigateur (retirés par le script).
--}}
@php
    $isLoop = $animation->isLoop();
    $poster = $animation->posterUrl();
    $title = $animation->titre;
    $kind = $isLoop ? 'Animation' : 'Clip';
    $label = $kind.' « '.$title.' »';
    $durationLabel = $animation->durationLabel() ?? '0:00';
    $uid = 'ap-'.$animation->id.'-'.\Illuminate\Support\Str::random(4);
@endphp

@include('animations.partials.assets')

<div {{ $attributes->class(['ap', 'ap--loop' => $isLoop, 'ap--clip' => ! $isLoop]) }}
    id="{{ $uid }}"
    data-animation-player
    data-mode="{{ $isLoop ? 'boucle' : 'clip' }}"
    @if ($viewUrl) data-view-url="{{ $viewUrl }}" @endif
    @if ($animation->duree_s) data-duration="{{ (float) $animation->duree_s }}" @endif
    data-title="{{ $title }}"
    style="--ap-ratio: {{ $animation->aspectRatio() }};"
    role="group"
    aria-roledescription="lecteur vidéo"
    aria-label="{{ $label }}"
    tabindex="0">

    <div class="ap__stage">
        @if ($isLoop)
            <video class="ap__video" autoplay muted loop playsinline preload="metadata"
                @if ($poster) poster="{{ $poster }}" @endif
                @if ($animation->largeur) width="{{ $animation->largeur }}" height="{{ $animation->hauteur }}" @endif
                disablepictureinpicture aria-label="{{ $label }}">
        @else
            <video class="ap__video" controls playsinline preload="metadata"
                @if ($poster) poster="{{ $poster }}" @endif
                @if ($animation->largeur) width="{{ $animation->largeur }}" height="{{ $animation->hauteur }}" @endif
                aria-label="{{ $label }}">
        @endif
            @if ($animation->webmUrl())
                <source src="{{ $animation->webmUrl() }}" type="video/webm; codecs=vp9">
            @endif
            <source src="{{ $animation->mp4Url() }}" type="video/mp4">
            <p>Ton navigateur ne peut pas lire cette vidéo. <a href="{{ $animation->mp4Url() }}">Ouvrir la vidéo</a>.</p>
        </video>

        {{-- Gros bouton central : clip à lancer, ou boucle en pause (« réduire les animations », pause manuelle). --}}
        <button type="button" class="ap__big" data-ap-big hidden aria-label="Lire {{ $isLoop ? 'l’animation' : 'le clip' }} « {{ $title }} »">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.5v13l10.5-6.5L8 5.5Z" fill="currentColor"/></svg>
        </button>

        {{-- Annonces pour les lecteurs d'écran (lecture, pause, muet). --}}
        <p class="sr-only" data-ap-live aria-live="polite"></p>
    </div>

    @if ($isLoop)
        <div class="ap__corner" data-ap-controls hidden>
            <button type="button" class="ap__btn" data-ap-toggle aria-pressed="false" aria-label="Mettre l’animation en pause">
                <svg class="ap__icon-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor"/></svg>
                <svg class="ap__icon-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.5v13l10.5-6.5L8 5.5Z" fill="currentColor"/></svg>
            </button>
        </div>
    @else
        <div class="ap__bar" data-ap-controls hidden>
            <button type="button" class="ap__btn" data-ap-toggle aria-pressed="false" aria-label="Lecture">
                <svg class="ap__icon-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor"/></svg>
                <svg class="ap__icon-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.5v13l10.5-6.5L8 5.5Z" fill="currentColor"/></svg>
            </button>

            <span class="ap__time" aria-hidden="true"><span data-ap-current>0:00</span> / <span data-ap-total>{{ $durationLabel }}</span></span>

            <label class="sr-only" for="{{ $uid }}-seek">Position dans {{ $isLoop ? 'l’animation' : 'le clip' }}</label>
            <input type="range" class="ap__seek" id="{{ $uid }}-seek" data-ap-seek
                min="0" max="{{ (float) ($animation->duree_s ?: 0) }}" step="0.1" value="0"
                aria-valuenow="0"
                aria-valuetext="0:00 sur {{ $durationLabel }}">

            <button type="button" class="ap__btn" data-ap-mute aria-pressed="false" aria-label="Couper le son">
                <svg class="ap__icon-sound" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9.5h3.5L12 5v14l-4.5-4.5H4z" fill="currentColor"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18 6a8.5 8.5 0 0 1 0 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <svg class="ap__icon-muted" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9.5h3.5L12 5v14l-4.5-4.5H4z" fill="currentColor"/><path d="m16 9.5 5 5m0-5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>

            <label class="sr-only" for="{{ $uid }}-volume">Volume</label>
            <input type="range" class="ap__volume" id="{{ $uid }}-volume" data-ap-volume min="0" max="1" step="0.05" value="1"
                aria-valuetext="100 %">

            <button type="button" class="ap__btn" data-ap-fullscreen aria-pressed="false" aria-label="Plein écran">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <p class="ap__keys" data-ap-keys hidden>Raccourcis : Espace (lecture/pause), ← → (5 s), M (muet), F (plein écran).</p>
    @endif
</div>
