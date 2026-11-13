{{-- Silhouette du Plateau (SVG maison, décoratif). Couleurs selon le mode : voir .nb-skyline dans world-neo.css. --}}
@php
    // [x, largeur, hauteur, classe] — tours du fond puis du premier plan.
    $back = [[20, 70, 150], [150, 60, 190], [330, 80, 170], [560, 64, 210], [760, 70, 160], [1090, 76, 200], [1270, 64, 170], [1380, 70, 140]];
    $front = [[70, 90, 120], [220, 70, 200], [300, 46, 110], [420, 100, 150], [640, 84, 240], [1000, 80, 170], [1180, 96, 130], [1320, 60, 100]];
    $windowClasses = ['w1', 'w2', 'w3', 'w1'];
@endphp
<svg {{ $attributes->merge(['class' => 'nb-skyline']) }} viewBox="0 0 1440 260" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
    @foreach ($back as [$x, $w, $h])
        <rect class="t2" x="{{ $x }}" y="{{ 260 - $h }}" width="{{ $w }}" height="{{ $h }}" />
    @endforeach
    {{-- La Pyramide (gradins) --}}
    <g class="pyr">
        <polygon points="800,260 980,260 966,232 814,232" />
        <polygon points="814,232 966,232 950,204 830,204" />
        <polygon points="830,204 950,204 934,176 846,176" />
        <polygon points="846,176 934,176 918,148 862,148" />
        <polygon points="862,148 918,148 902,120 878,120" />
        <polygon points="878,120 902,120 890,96" />
    </g>
    @foreach ($front as $i => [$x, $w, $h])
        <rect class="t" x="{{ $x }}" y="{{ 260 - $h }}" width="{{ $w }}" height="{{ $h }}" />
        @for ($row = 0; $row < intdiv($h - 30, 26); $row++)
            @for ($col = 0; $col < intdiv($w - 14, 18); $col++)
                @if (($row * 3 + $col + $i) % 4 !== 0)
                    <rect class="{{ $windowClasses[($row + $col + $i) % 4] }}" x="{{ $x + 10 + $col * 18 }}" y="{{ 260 - $h + 18 + $row * 26 }}" width="8" height="12" />
                @endif
            @endfor
        @endfor
    @endforeach
    {{-- antenne --}}
    <rect class="t" x="680" y="0" width="4" height="22" />
</svg>
