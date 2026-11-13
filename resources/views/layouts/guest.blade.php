<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    @include('layouts.partials.meta', ['robotsNoindex' => true])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Noto+Sans+JP:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body data-world="pont" data-mode="nuit" class="pa flex min-h-screen flex-col antialiased">
    <a href="#contenu" class="w-skip">Aller au contenu</a>

    <div class="pa-top w-container">
        <a href="{{ route('public.index') }}" class="pa-back" aria-label="Retour au site — Retour à l'accueil {{ \App\Support\Fr::de($collectif['nom']) }}">
            <span aria-hidden="true">←</span> Retour au site
        </a>
    </div>

    <main id="contenu" class="pa-main">
        <div class="pa-deco" aria-hidden="true">
            <span class="pa-welcome hum">ようこそ</span>
            <span class="pa-studio hum">STUDIO BABI</span>
            <span class="pa-moon drift"></span>
            <span class="pa-cloud drift"></span>
            <span class="pa-once hum">il était une fois…</span>
        </div>

        <div class="pa-card">
            <a href="{{ route('public.index') }}" class="pa-logo" aria-label="{{ $collectif['nom'] }} — accueil">
                <span class="pa-logo__mark" aria-hidden="true"><span>A</span><span>D</span></span>
            </a>
            {{ $slot }}
        </div>
    </main>

    @include('layouts.worlds.footer')
</body>
</html>
