{{--
    Réseaux sociaux du collectif (admin › Réglages › Contact & réseaux), en icônes.
    Un réseau vide n'apparaît pas ; sans aucun réseau, rien n'est affiché.
    Paramètre facultatif : $class (classe de la liste).
--}}
@php
    $socialLinks = $collectif['reseaux'] ?? [];
@endphp
@if (count($socialLinks))
    <ul class="{{ $class ?? 'w-social' }}" aria-label="Réseaux sociaux du collectif">
        @foreach ($socialLinks as $social)
            <li>
                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" class="w-social__link"
                    aria-label="{{ $social['label'] }} (nouvel onglet)">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        @switch ($social['key'])
                            @case ('instagram')
                                <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                                @break
                            @case ('facebook')
                                <path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V10H6.5v3.5H9V21h3.5v-7.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15z"/>
                                @break
                            @case ('tiktok')
                                <path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.4 2.8 2.2 4.6 5 5"/>
                                @break
                            @case ('youtube')
                                <rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="m10 9 5 3-5 3z" fill="currentColor"/>
                                @break
                            @case ('behance')
                                <path d="M3 6h5a3 3 0 0 1 0 6H3zM3 12h5.5a3 3 0 0 1 0 6H3z"/><path d="M14 13.5h7a3.5 3.5 0 1 0-1 3"/><path d="M15 7h5"/>
                                @break
                            @case ('x')
                                <path d="M4 4l16 16M20 4 4 20"/>
                                @break
                            @default
                                <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>
                        @endswitch
                    </svg>
                </a>
            </li>
        @endforeach
    </ul>
@endif
