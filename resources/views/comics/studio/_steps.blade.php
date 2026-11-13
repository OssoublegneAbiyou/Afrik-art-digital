@php
    // $current : 1 (fiche), 2 (pages), 3 (publication) ; $comic et $chapter facultatifs (étape 1 d'un nouveau récit).
    $steps = [
        1 => ['Le récit', $comic ? route('comics.studio.edit', array_filter([$comic, 'chapitre' => $chapter?->id])) : null],
        2 => [$comic && ! $comic->isOneShot() && $chapter ? 'Les pages du ch. '.$chapter->numberLabel() : 'Les pages', $comic && $chapter ? route('comics.studio.chapters.pages', [$comic, $chapter]) : null],
        3 => ['Publication', $comic && $chapter ? route('comics.studio.chapters.publication', [$comic, $chapter]) : null],
    ];
@endphp
<nav class="cs-steps" aria-label="Étapes">
    <ol role="list">
        @foreach ($steps as $number => [$label, $url])
            <li class="cs-step {{ $number === $current ? 'is-current' : '' }} {{ $number < $current ? 'is-done' : '' }}">
                @if ($url && $number !== $current)
                    <a href="{{ $url }}"><span class="cs-step__n" aria-hidden="true">{{ $number }}</span> {{ $label }}<span class="sr-only"> (étape {{ $number }} sur 3)</span></a>
                @else
                    <span @if ($number === $current) aria-current="step" @endif><span class="cs-step__n" aria-hidden="true">{{ $number }}</span> {{ $label }}<span class="sr-only"> (étape {{ $number }} sur 3)</span></span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
