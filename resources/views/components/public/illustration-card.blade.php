@props(['illustration', 'showArtist' => true])

{{-- Tuile d'œuvre Néo-Babi : portrait 3:4, étiquette de type, bordure/ombre de la couleur du type, inclinaison au survol. --}}
@php
    $artistName = $illustration->artist?->user?->name ?? 'Artiste';
    $tone = match ($illustration->type) {
        'croquis' => 'a2',
        'couleur' => 'a1',
        'couverture' => 'a3',
        default => 'line',
    };
@endphp

<article {{ $attributes->merge(['class' => 'nb-tile nb-tile--'.$tone]) }}>
    <a href="{{ route('public.illustration', $illustration) }}" class="nb-tile__frame tilt">
        <img
            src="{{ $illustration->thumbnailUrl() }}"
            alt="{{ $illustration->title }} par {{ $artistName }}"
            class="neo-thumb"
            loading="lazy"
            decoding="async"
        >
        @if ($illustration->typeLabel())
            <span class="nb-tile__type">{{ $illustration->typeLabel() }}</span>
        @endif
    </a>
    <div class="nb-tile__body">
        <h3>
            <a href="{{ route('public.illustration', $illustration) }}" class="nb-tile__title">{{ $illustration->title }}</a>
        </h3>
        @if ($showArtist && $illustration->artist)
            <a href="{{ route('public.artist', $illustration->artist) }}" class="nb-tile__artist">{{ $artistName }}</a>
        @endif
        {{-- Compteur réel, affiché seulement quand la requête l'a chargé (withCount) et qu'il n'est pas nul. --}}
        @if ((int) $illustration->likes_count > 0)
            <p class="likes-badge nb-tile__likes">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2Z"/></svg>
                {{ $illustration->likes_count }} <span class="sr-only">{{ (int) $illustration->likes_count >= 2 ? 'personnes aiment' : 'personne aime' }}</span>
            </p>
        @endif
        @if (trim($slot) !== '')
            <div class="nb-tile__actions">{{ $slot }}</div>
        @endif
    </div>
</article>
