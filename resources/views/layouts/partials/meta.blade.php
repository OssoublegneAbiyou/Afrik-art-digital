@php
    // Réglages du collectif (admin › Réglages › Identité / Présentation / SEO).
    $siteName = $collectif['nom'] ?? config('site.name');
    $titleSuffix = setting('seo.suffixe_titre') ?: '| '.$siteName;
    $defaultTitle = setting('seo.titre_par_defaut')
        ?: $siteName.(filled($collectif['description_courte'] ?? null) ? ' — '.$collectif['description_courte'] : '');
    $pageTitle = $title ? $title.' '.$titleSuffix : $defaultTitle;
    $pageDescription = \Illuminate\Support\Str::limit(strip_tags($description ?: (string) setting('presentation.meta_description')), 180);
    $pageImage = $image ?: ($collectif['image_partage_url'] ?? null) ?: asset('images/og-default.png');
    $faviconUrl = $collectif['favicon_url'] ?? null;
@endphp
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
@if (! setting('seo.indexation', true) && empty($robotsNoindex))
    <meta name="robots" content="noindex">
@endif
<link rel="canonical" href="{{ url()->current() }}">
@if ($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}">
@else
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
@endif
<meta name="theme-color" content="#181818">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="fr_FR">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $pageImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $pageImage }}">
