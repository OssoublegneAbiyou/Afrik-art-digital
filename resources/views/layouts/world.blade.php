<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($robotsNoindex)
        <meta name="robots" content="noindex">
    @endif
    @include('layouts.partials.meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Chakra+Petch:wght@400;600;700&family=Noto+Sans+JP:wght@700;900&family=Space+Grotesk:wght@500;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Nunito+Sans:wght@400;600;700&family=Literata:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    {{ $head ?? '' }}
</head>
<body data-world="{{ $world }}" data-mode="{{ $defaultMode }}" @if ($forceMode) data-mode-locked="true" @endif class="flex min-h-screen flex-col antialiased">
    {{-- Applique le mode mémorisé avant le premier rendu (pas de flash). --}}
    <script>
        (function () {
            var b = document.body, w = b.dataset.world;
            if (b.dataset.modeLocked) return;
            var m = b.dataset.mode;
            try { m = localStorage.getItem('aad-mode-' + w) || m; } catch (e) {}
            if (m === 'jour' || m === 'nuit') b.dataset.mode = m;
        })();
    </script>

    <a href="#contenu" class="w-skip">Aller au contenu</a>

    {{-- Annonce active (admin › Annonces). --}}
    @includeIf('layouts.partials.announcement')

    {{-- Bienvenue après « Continuer avec Google » (nouveau compte visiteur). --}}
    @include('layouts.partials.google-welcome')

    @include('layouts.worlds.header', ['world' => $world, 'hasModeToggle' => $hasModeToggle(), 'overlay' => $overlayHeader])

    <main id="contenu" class="relative flex-1">
        {{ $slot }}
    </main>

    @include('layouts.worlds.footer')

    <script>
        (function () {
            var b = document.body, w = b.dataset.world;
            function sync() {
                document.querySelectorAll('[data-set-mode]').forEach(function (x) {
                    x.setAttribute('aria-pressed', String(x.dataset.setMode === b.dataset.mode));
                });
                document.dispatchEvent(new CustomEvent('aad:mode', { detail: b.dataset.mode }));
            }
            // Menus déroulants (<details>) : fermeture au clic extérieur et avec Échap.
            document.addEventListener('click', function (e) {
                document.querySelectorAll('details.w-menu[open], details.w-account[open]').forEach(function (d) {
                    if (!d.contains(e.target)) d.removeAttribute('open');
                });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                document.querySelectorAll('details.w-menu[open], details.w-account[open]').forEach(function (d) {
                    d.removeAttribute('open');
                    d.querySelector('summary').focus();
                });
            });
            document.addEventListener('click', function (e) {
                var t = e.target.closest('[data-set-mode]');
                if (!t || b.dataset.modeLocked) return;
                b.dataset.mode = t.dataset.setMode;
                try { localStorage.setItem('aad-mode-' + w, t.dataset.setMode); } catch (err) {}
                sync();
            });
            sync();
        })();
    </script>
</body>
</html>
