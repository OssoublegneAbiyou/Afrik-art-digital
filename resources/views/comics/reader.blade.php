@php
    use App\Support\Fr;

    $authorName = $comic->user?->name ?? 'Artiste';
    $isOneShot = $comic->isOneShot();
    $chapterHeading = ($isOneShot ? $comic->titre : 'Chapitre '.$chapter->numberLabel()).($chapter->titre && ! $isOneShot ? ' — '.$chapter->titre : '');
    $pageTitle = $isOneShot ? $comic->titre : $comic->titre.' – ch. '.$chapter->numberLabel();
    $visible = $chapter->isVisible() && $comic->isVisible();
    $mode = $config['mode'];
    $dir = $config['dir'];
    $firstSlice = $pages->first()['slices'][0]['url'] ?? null;
    $chevron = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 5 7 7-7 7"/></svg>';
    $title = $pageTitle;
    $description = 'Lire « '.$comic->titre.' »'.($isOneShot ? '' : ', chapitre '.$chapter->numberLabel()).', '.mb_strtolower($comic->typeLabel()).' '.Fr::de($authorName).'.';
    $image = $comic->coverUrl();
    $robotsNoindex = ! $visible;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    {{-- Le zoom natif du navigateur reste autorisé (pas de user-scalable=no). --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($robotsNoindex)
        <meta name="robots" content="noindex">
    @endif
    @include('layouts.partials.meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    @if ($firstSlice)
        <link rel="preload" as="image" href="{{ $firstSlice }}" fetchpriority="high">
    @endif
    @vite(['resources/css/app.css', 'resources/js/comic-reader.js'])
</head>
<body data-world="neo" data-mode="nuit" class="cr-body">
    {{-- Mode Jour / Nuit mémorisé pour l'univers Néo (même clé que le reste du site). --}}
    <script>
        (function () {
            var m = null;
            try { m = localStorage.getItem('aad-mode-neo'); } catch (e) {}
            if (m === 'jour' || m === 'nuit') document.body.dataset.mode = m;
        })();
    </script>

    <a href="#cr-stage" class="w-skip">Aller aux planches</a>

    <div class="cr" id="cr" data-comic-reader data-mode="{{ $mode }}" data-dir="{{ $dir }}" data-fit="hauteur"
        data-config="{{ json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">

        <div class="cr-progress" aria-hidden="true"><span class="cr-progress__bar" data-cr-progress></span></div>

        {{-- ===== Barre du haut ===== --}}
        <header class="cr-bar cr-bar--top" data-cr-bar>
            <a href="{{ route('comics.show', $comic) }}" class="cr-icon-btn" aria-label="Retour à la fiche de « {{ $comic->titre }} »">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M15 5 8 12l7 7"/></svg>
            </a>
            <div class="cr-bar__titles">
                @unless ($isOneShot)
                    <p class="cr-bar__series">{{ $comic->titre }}</p>
                @endunless
                <h1 class="cr-bar__chapter">{{ $chapterHeading }}</h1>
            </div>
            <button type="button" class="cr-icon-btn" data-cr-fullscreen aria-label="Plein écran (touche F)" aria-pressed="false" hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
            </button>
            <button type="button" class="cr-icon-btn" data-cr-settings-toggle aria-label="Réglages de lecture" aria-expanded="false" aria-controls="cr-settings">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
            </button>
        </header>

        {{-- ===== Réglages (⚙) ===== --}}
        <section class="cr-settings" id="cr-settings" aria-labelledby="cr-settings-title" data-cr-settings hidden>
            <h2 id="cr-settings-title" class="cr-settings__title">Réglages de lecture</h2>
            <fieldset>
                <legend>Mode de lecture</legend>
                <label><input type="radio" name="cr-mode" value="vertical" @checked($mode === 'vertical')> Défilement vertical</label>
                <label><input type="radio" name="cr-mode" value="pages" @checked($mode === 'pages')> Page par page</label>
            </fieldset>
            <fieldset data-cr-only="vertical">
                <legend>Espacement entre les pages</legend>
                <label><input type="radio" name="cr-gap" value="0" checked> Aucun</label>
                <label><input type="radio" name="cr-gap" value="8"> 8 px</label>
                <label><input type="radio" name="cr-gap" value="24"> 24 px</label>
            </fieldset>
            <fieldset data-cr-only="pages">
                <legend>Ajustement de la page</legend>
                <label><input type="radio" name="cr-fit" value="hauteur" checked> À la hauteur de l’écran</label>
                <label><input type="radio" name="cr-fit" value="largeur"> À la largeur de l’écran</label>
            </fieldset>
            <fieldset data-cr-only="pages">
                <legend>Double page <small>(écran large, en paysage)</small></legend>
                <label><input type="checkbox" name="cr-double" checked> Deux pages côte à côte</label>
                <label><input type="checkbox" name="cr-cover" checked> Page de couverture seule</label>
            </fieldset>
            <fieldset>
                <legend>Fond</legend>
                <label><input type="radio" name="cr-theme" value="nuit"> Sombre</label>
                <label><input type="radio" name="cr-theme" value="jour"> Clair</label>
            </fieldset>
            <p class="cr-settings__hint">Clavier : ← → pour tourner les pages, Espace pour avancer, F pour le plein écran, Échap pour fermer ce menu.</p>
            <button type="button" class="cr-btn" data-cr-settings-close>Fermer</button>
        </section>

        @if ($preview && ! $visible)
            <p class="cr-preview" role="status">
                Aperçu — {{ $comic->isVisible() ? $chapter->statusLabel() : $comic->statusLabel() }} : ce {{ $isOneShot ? 'récit' : 'chapitre' }} n’est visible que par toi et l’équipe.
                @if ($pendingPages > 0)
                    {{ Fr::count($pendingPages, 'page encore en préparation', 'pages encore en préparation') }}.
                @endif
            </p>
        @elseif ($preview && $pendingPages > 0)
            <p class="cr-preview" role="status">{{ Fr::count($pendingPages, 'page encore en préparation', 'pages encore en préparation') }} : elle{{ $pendingPages >= 2 ? 's' : '' }} apparaîtr{{ $pendingPages >= 2 ? 'ont' : 'a' }} dès la fin de la conversion.</p>
        @endif

        @if ($dir === 'rtl')
            <p class="cr-hint" data-cr-rtl-hint role="status" hidden>
                <span aria-hidden="true">←</span> Lecture de droite à gauche
                <button type="button" class="cr-hint__close" data-cr-hint-close aria-label="Fermer le rappel">×</button>
            </p>
        @endif

        {{-- ===== Avertissement « contenu sensible » (mémorisé pour la session) ===== --}}
        @if ($comic->contenu_sensible && ! $warningAccepted)
            <div class="cr-warning" data-cr-warning role="alertdialog" aria-modal="true" aria-labelledby="cr-warning-title" aria-describedby="cr-warning-text">
                <div class="cr-warning__box">
                    <p class="cr-warning__kicker" aria-hidden="true">⚠</p>
                    <h2 id="cr-warning-title" class="cr-warning__title">Contenu sensible</h2>
                    <p id="cr-warning-text">Ce récit contient des scènes qui peuvent heurter (violence, horreur…). Continuer ?</p>
                    <div class="cr-warning__actions">
                        <a href="{{ request()->fullUrlWithQuery(['avertissement' => 'lu']) }}" class="cr-btn cr-btn--primary" data-cr-warning-accept>Continuer la lecture</a>
                        <a href="{{ route('comics.show', $comic) }}" class="cr-btn">Retour à la fiche</a>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===== Planches ===== --}}
        <main class="cr-stage" id="cr-stage" tabindex="-1" aria-label="Planches">
            @if ($total === 0)
                <p class="cr-empty">Les pages de ce {{ $isOneShot ? 'récit' : 'chapitre' }} sont en cours de préparation. Reviens dans quelques instants.</p>
            @endif

            <ol class="cr-pages" data-cr-pages role="list">
                @foreach ($pages as $page)
                    <li class="cr-page is-loading {{ count($page['slices']) > 1 ? 'is-tall' : '' }}" id="page-{{ $page['n'] }}" data-cr-page="{{ $page['n'] - 1 }}" data-double="{{ $page['double'] ? '1' : '0' }}">
                        <div class="cr-page__inner">
                            @foreach ($page['slices'] as $k => $slice)
                                <img src="{{ $slice['url'] }}" alt="{{ $k === 0 ? $page['alt'] : '' }}"
                                    @if ($slice['width'] && $slice['height']) width="{{ $slice['width'] }}" height="{{ $slice['height'] }}" @endif
                                    decoding="async"
                                    @if ($page['n'] > 3) loading="lazy" @elseif ($page['n'] === 1 && $k === 0) fetchpriority="high" @endif
                                    class="cr-img" draggable="false">
                            @endforeach
                            @if (! empty($page['video']))
                                {{-- Case animée : boucle muette posée sur son poster, lue seulement quand la page est à l'écran. --}}
                                <video class="cr-page__video" data-cr-video src="{{ $page['video'] }}" poster="{{ $page['slices'][0]['url'] ?? '' }}"
                                    muted loop playsinline preload="none" aria-hidden="true" tabindex="-1"></video>
                                <button type="button" class="cr-btn cr-page__play" data-cr-video-toggle aria-pressed="false" hidden>
                                    <span data-cr-video-label>Lire l’animation</span><span class="sr-only"> de la page {{ $page['n'] }}</span>
                                </button>
                            @endif
                        </div>
                        <span class="cr-page__loader" aria-hidden="true"></span>
                        <button type="button" class="cr-btn cr-page__retry" data-cr-retry hidden>Réessayer<span class="sr-only"> de charger la page {{ $page['n'] }}</span></button>
                    </li>
                @endforeach
            </ol>

            {{-- ===== Fin de chapitre ===== --}}
            <section class="cr-end" id="cr-end" data-cr-end aria-labelledby="cr-end-title" tabindex="-1">
                <h2 id="cr-end-title" class="cr-end__title">{{ $isOneShot ? 'Fin' : 'Fin du chapitre '.$chapter->numberLabel() }}</h2>

                @if (filled($chapter->note_auteur))
                    <figure class="cr-end__note">
                        <figcaption>Le mot {{ Fr::deArticle($authorName) }}</figcaption>
                        <blockquote>{!! nl2br(e($chapter->note_auteur)) !!}</blockquote>
                    </figure>
                @endif

                @if ($next)
                    <a href="{{ route('comics.read', [$comic, $next->routeNumber()]) }}" class="cr-end__next" rel="next">
                        <span>Chapitre suivant <span aria-hidden="true">→</span></span>
                        <small>Ch. {{ $next->numberLabel() }}{{ $next->titre ? ' — '.$next->titre : '' }}</small>
                    </a>
                @elseif ($isOneShot || $comic->statut_serie === 'terminee')
                    <p class="cr-end__done">Fin — merci d’avoir lu !</p>
                @else
                    <p class="cr-end__soon">Prochain chapitre bientôt… <span>Suis la série pour être prévenu de sa sortie.</span></p>
                @endif

                <div class="cr-end__actions">
                    @if ($visible)
                        <x-like-button :item="$comic" />
                    @endif
                    @unless ($isOwner)
                        @auth
                            @if ($visible)
                                <form method="POST" action="{{ route('comics.follow', $comic) }}">
                                    @csrf
                                    <button type="submit" class="cr-btn {{ $isFollowing ? '' : 'cr-btn--primary' }}" aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
                                        {{ $isFollowing ? 'Ne plus suivre' : ($isOneShot ? 'Suivre' : 'Suivre la série') }}
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ \App\Support\GoogleAuth::loginUrl() }}" class="cr-btn cr-btn--primary">Se connecter pour suivre {{ $isOneShot ? 'l’auteur' : 'la série' }}</a>
                        @endauth
                    @endunless
                </div>

                @if ($visible)
                    <x-share-buttons class="cr-end__share" :title="'« '.$comic->titre.' » '.Fr::de($authorName).' sur '.$collectif['nom']" :url="route('comics.show', $comic)" label="Partager le récit" />
                    <x-report-button class="cr-end__report" :work="$isOneShot ? $comic : $chapter" />
                @endif

                <p class="cr-end__links">
                    @if ($previous)
                        <a href="{{ route('comics.read', [$comic, $previous->routeNumber()]) }}" rel="prev">← Ch. {{ $previous->numberLabel() }}</a>
                    @endif
                    <a href="{{ route('comics.show', $comic) }}" class="cr-end__link">Fiche du récit</a>
                </p>

                @if ($sameStudio->isNotEmpty() || $studioIllustrations->isNotEmpty() || $studioArtist)
                    <div class="cr-end__same">
                        <h3 class="cr-end__subtitle">Du même studio</h3>
                        <ul class="cr-end__grid" role="list">
                            @foreach ($studioIllustrations as $illustration)
                                <li>
                                    <a href="{{ route('public.illustration', $illustration) }}" class="cr-mini">
                                        <img src="{{ $illustration->thumbnailUrl() }}" alt="" width="200" height="300" loading="lazy" decoding="async">
                                        <span class="cr-mini__title">{{ $illustration->title }}</span>
                                        <span class="cr-mini__meta">Illustration</span>
                                    </a>
                                </li>
                            @endforeach
                            @foreach ($sameStudio as $other)
                                <li>
                                    <a href="{{ route('comics.show', $other) }}" class="cr-mini">
                                        @if ($other->coverUrl())
                                            <img src="{{ $other->coverUrl() }}" alt="" width="200" height="300" loading="lazy" decoding="async">
                                        @else
                                            <span class="cr-mini__placeholder" aria-hidden="true">漫画</span>
                                        @endif
                                        <span class="cr-mini__title">{{ $other->titre }}</span>
                                        <span class="cr-mini__meta">{{ $other->typeLabel() }} · {{ $other->readingKind() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        @if ($studioArtist)
                            <p class="cr-end__links">
                                <a href="{{ route('public.artist', $studioArtist) }}" class="cr-end__link">Voir le studio {{ Fr::de($authorName) }} →</a>
                            </p>
                        @endif
                    </div>
                @endif
            </section>
        </main>

        <p class="sr-only" aria-live="polite" aria-atomic="true" data-cr-live>Page {{ $start }} sur {{ $total }}</p>

        {{-- ===== Barre du bas ===== --}}
        <footer class="cr-bar cr-bar--bottom" data-cr-bar>
            @if ($previous)
                <a href="{{ route('comics.read', [$comic, $previous->routeNumber()]) }}" class="cr-chap cr-chap--prev" rel="prev" aria-label="Chapitre précédent : ch. {{ $previous->numberLabel() }}">
                    <span aria-hidden="true">«</span><span class="cr-chap__label">Ch. {{ $previous->numberLabel() }}</span>
                </a>
            @else
                <span class="cr-chap" aria-hidden="true"></span>
            @endif

            <div class="cr-nav">
                <button type="button" class="cr-icon-btn cr-nav__prev" data-cr-prev aria-label="Page précédente">{!! $chevron !!}</button>
                <p class="cr-counter"><span data-cr-current>{{ $start }}</span> / {{ $total }}</p>
                <button type="button" class="cr-icon-btn cr-nav__next" data-cr-next aria-label="Page suivante">{!! $chevron !!}</button>
            </div>

            @if ($next)
                <a href="{{ route('comics.read', [$comic, $next->routeNumber()]) }}" class="cr-chap cr-chap--next" rel="next" aria-label="Chapitre suivant : ch. {{ $next->numberLabel() }}">
                    <span class="cr-chap__label">Ch. {{ $next->numberLabel() }}</span><span aria-hidden="true">»</span>
                </a>
            @else
                <span class="cr-chap" aria-hidden="true"></span>
            @endif
        </footer>
    </div>
</body>
</html>
