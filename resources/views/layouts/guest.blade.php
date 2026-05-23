<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Afrik\'art Digital') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center bg-transparent px-6 py-10 sm:pt-0">
            <div class="mb-4 text-center">
                <a href="/" class="bg-gradient-to-r from-[#ef476f] via-[#ff7b54] to-[#14b8a6] bg-clip-text text-2xl font-semibold text-transparent">
                    Afrik'art Digital
                </a>
            </div>

            <div class="w-full overflow-hidden rounded-[1.75rem] border border-white/70 bg-white/80 px-6 py-6 shadow-[0_20px_60px_rgba(255,161,90,0.18)] backdrop-blur sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
