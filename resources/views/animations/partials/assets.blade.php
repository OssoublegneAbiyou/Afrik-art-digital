{{--
    Script du lecteur d'animations (entrée Vite resources/js/animation-player.js), chargé une seule fois par page,
    seulement là où un lecteur ou une carte d'animation est affiché. Si le build n'a pas encore été refait
    (entrée absente du manifeste), la page s'affiche quand même : les vidéos gardent leurs attributs natifs
    (boucle muette en autoplay, contrôles du navigateur pour les clips).
--}}
@once
    @php
        try {
            echo app(\Illuminate\Foundation\Vite::class)('resources/js/animation-player.js');
        } catch (\Illuminate\Foundation\ViteException $e) {
            report($e);
        }
    @endphp
@endonce
