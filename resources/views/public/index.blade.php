@php
    use App\Models\Document;
    use App\Models\Illustration;
    use App\Support\Fr;
    use Illuminate\Support\Str;

    $artistName = $featuredArtist?->user?->name;
    $artistWorks = $featuredArtist?->illustrations ?? collect();
    $artistHighlight = $artistWorks->first();

    $writerName = $featuredWriter?->user?->name;
    $writerWorks = $featuredWriter?->documents ?? collect();
    $writerHighlight = $writerWorks->first();

    $featuredCount = ($featuredArtist ? 1 : 0) + ($featuredWriter ? 1 : 0);

    // Réglages du collectif (admin › Réglages › Présentation).
    $heroTitle1 = setting('presentation.accueil_titre_1');
    $heroTitle2 = setting('presentation.accueil_titre_2');
    $heroIntro = setting('presentation.accueil_intro');
    $collectiveText = $collectiveText ?? \App\Support\Settings::text('presentation.collectif_court');

    $stats = collect([
        ['n' => $totals['artists'] ?? 0, 'one' => 'illustrateur', 'many' => 'illustrateurs'],
        ['n' => $totals['writers'] ?? 0, 'one' => 'écrivain', 'many' => 'écrivains'],
        ['n' => $totals['illustrations'] ?? 0, 'one' => 'illustration', 'many' => 'illustrations'],
        ['n' => $totals['documents'] ?? 0, 'one' => 'récit', 'many' => 'récits'],
    ])->filter(fn ($s) => $s['n'] > 0);
@endphp

<x-world-layout
    world="pont"
    :description="$collectif['nom'].', vitrine d\'un collectif d\'illustrateurs et d\'écrivains d\'Abidjan : illustrations, portfolios immersifs et récits à lire en ligne.'"
    :image="$dailyIllustrations->first()?->thumbnailUrl()"
>
    <div class="ph">
        {{-- Halos : un seul fond continu (dégradé du body) + lueurs floues, aucune coupure entre sections. --}}
        <div class="ph-halos w-deco" aria-hidden="true">
            <div class="pont-halo" style="width:620px;height:620px;top:-160px;right:-140px;background:rgba(255,46,136,.20)"></div>
            <div class="pont-halo" style="width:520px;height:520px;top:420px;left:-220px;background:rgba(46,242,255,.12)"></div>
            <div class="pont-halo" style="width:640px;height:640px;top:1100px;right:-260px;background:rgba(201,184,255,.16)"></div>
            <div class="pont-halo" style="width:560px;height:560px;top:1900px;left:-200px;background:rgba(255,46,136,.12)"></div>
            <div class="pont-halo" style="width:460px;height:460px;bottom:80px;right:-120px;background:rgba(46,242,255,.10)"></div>
        </div>

        {{-- 1. Hero --}}
        <section class="ph-hero" aria-labelledby="ph-hero-title">
            <div class="w-container ph-hero__grid">
                <div class="ph-hero__text">
                    <p class="ph-kicker">{{ 'Vitrine créative'.(filled($collectif['ville']) ? ' · '.$collectif['ville'] : '') }}</p>
                    <h1 id="ph-hero-title" class="ph-title">
                        @if (filled($heroTitle1))
                            <span class="font-bebas ph-title__bebas">{{ $heroTitle1 }}</span>
                        @endif
                        @if (filled($heroTitle2))
                            <span class="font-cormorant ph-title__italic">{{ $heroTitle2 }}</span>
                        @endif
                    </h1>
                    @if (filled($heroIntro))
                        <p class="ph-lead">{{ $heroIntro }}</p>
                    @endif
                    <div class="ph-actions">
                        <a href="{{ route('public.illustrations') }}" class="pont-btn pont-btn--rose">Voir les illustrations</a>
                        <a href="{{ route('public.literary-works') }}" class="pont-btn pont-btn--ghost">Lire les histoires</a>
                    </div>
                    @if ($stats->isNotEmpty())
                        <ul class="ph-stats" aria-label="Le collectif en chiffres">
                            @foreach ($stats as $stat)
                                <li><strong>{{ $stat['n'] }}</strong> {{ $stat['n'] >= 2 ? $stat['many'] : $stat['one'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="ph-scene w-deco" aria-hidden="true">
                    <div class="ph-moon drift"><span class="ph-moon__ring"></span></div>
                    <span class="ph-kanji hum">夢</span>
                    <span class="ph-sign ph-sign--banner hum">バビの夜</span>
                    <span class="ph-sign ph-sign--once hum">il était une fois…</span>
                    <span class="ph-sign ph-sign--gbaka hum">GBAKA LINE 09</span>
                    <span class="ph-sign ph-sign--maquis hum">maquis des songes</span>
                    <div class="mist ph-mist ph-mist--1"></div>
                    <div class="mist ph-mist ph-mist--2"></div>
                </div>
            </div>

            <svg class="ph-skyline w-deco" aria-hidden="true" focusable="false" viewBox="0 0 1440 240" preserveAspectRatio="xMidYMax slice">
                <defs>
                    <linearGradient id="ph-sky-fade" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#fff" stop-opacity="1"/>
                        <stop offset=".55" stop-color="#fff" stop-opacity=".85"/>
                        <stop offset="1" stop-color="#fff" stop-opacity="0"/>
                    </linearGradient>
                    <mask id="ph-sky-mask"><rect width="1440" height="240" fill="url(#ph-sky-fade)"/></mask>
                </defs>
                <g mask="url(#ph-sky-mask)">
                    <g fill="#1A1240" opacity=".75">
                        <rect x="0" y="120" width="70" height="120"/><rect x="60" y="90" width="50" height="150"/>
                        <rect x="150" y="110" width="80" height="130"/><rect x="300" y="70" width="44" height="170"/>
                        <rect x="420" y="100" width="90" height="140"/><rect x="600" y="80" width="60" height="160"/>
                        <rect x="760" y="105" width="80" height="135"/><rect x="930" y="60" width="54" height="180"/>
                        <rect x="1060" y="95" width="90" height="145"/><rect x="1230" y="75" width="60" height="165"/>
                        <rect x="1340" y="110" width="100" height="130"/>
                    </g>
                    <g fill="#120C2C">
                        <rect x="20" y="150" width="90" height="90"/><rect x="110" y="128" width="56" height="112"/>
                        <rect x="180" y="96" width="70" height="144"/><path d="M200 96 l15-26 15 26z"/>
                        <rect x="262" y="140" width="80" height="100"/><rect x="350" y="110" width="48" height="130"/>
                        <rect x="406" y="60" width="62" height="180"/><rect x="430" y="40" width="6" height="22"/>
                        <rect x="480" y="132" width="96" height="108"/><rect x="590" y="100" width="58" height="140"/>
                        <rect x="660" y="148" width="110" height="92"/><rect x="780" y="88" width="66" height="152"/>
                        <path d="M780 88 h66 l-10-18 h-46z"/>
                        <rect x="858" y="122" width="78" height="118"/><rect x="948" y="72" width="50" height="168"/>
                        <rect x="1010" y="138" width="104" height="102"/><rect x="1124" y="100" width="64" height="140"/>
                        <rect x="1198" y="54" width="58" height="186"/><rect x="1222" y="34" width="5" height="22"/>
                        <rect x="1266" y="130" width="84" height="110"/><rect x="1360" y="96" width="80" height="144"/>
                    </g>
                    <g class="ph-windows">
                        <rect x="194" y="112" width="8" height="10" fill="#FF2E88"/><rect x="226" y="140" width="8" height="10" fill="#2EF2FF"/>
                        <rect x="418" y="80" width="8" height="10" fill="#FFE14D"/><rect x="446" y="118" width="8" height="10" fill="#FF2E88"/>
                        <rect x="420" y="160" width="8" height="10" fill="#2EF2FF"/><rect x="604" y="122" width="8" height="10" fill="#2EF2FF"/>
                        <rect x="626" y="170" width="8" height="10" fill="#FFE14D"/><rect x="796" y="108" width="8" height="10" fill="#FF2E88"/>
                        <rect x="824" y="150" width="8" height="10" fill="#FFE14D"/><rect x="960" y="96" width="8" height="10" fill="#2EF2FF"/>
                        <rect x="976" y="146" width="8" height="10" fill="#FF2E88"/><rect x="1140" y="124" width="8" height="10" fill="#FFE14D"/>
                        <rect x="1212" y="78" width="8" height="10" fill="#FF2E88"/><rect x="1234" y="130" width="8" height="10" fill="#2EF2FF"/>
                        <rect x="1378" y="120" width="8" height="10" fill="#FFE14D"/><rect x="124" y="150" width="8" height="10" fill="#2EF2FF"/>
                        <rect x="364" y="132" width="8" height="10" fill="#FF2E88"/><rect x="1060" y="160" width="8" height="10" fill="#2EF2FF"/>
                    </g>
                </g>
            </svg>
        </section>

        {{-- 2. À l'affiche ce soir --}}
        @if ($featuredCount > 0)
            <section class="w-container ph-section" aria-labelledby="ph-affiche-title">
                <h2 id="ph-affiche-title" class="ph-h2">
                    <span class="font-bebas">À l'affiche</span>
                    <span class="font-cormorant ph-h2__italic">ce soir</span>
                </h2>

                <div class="ph-affiche {{ $featuredCount === 1 ? 'ph-affiche--single' : '' }}">
                    @if ($featuredArtist)
                        <article class="glass-card ph-feature ph-feature--rose">
                            <a href="{{ route('public.artist', $featuredArtist) }}" class="ph-feature__thumb" tabindex="-1" aria-hidden="true">
                                @if ($artistHighlight)
                                    <img src="{{ $artistHighlight->thumbnailUrl() }}" alt="" loading="lazy">
                                @else
                                    <span class="ph-feature__initial">{{ Str::upper(Str::substr($artistName ?? '?', 0, 1)) }}</span>
                                @endif
                            </a>
                            <div class="ph-feature__body">
                                <p class="ph-feature__kicker">Artiste du jour</p>
                                <h3 class="ph-feature__name font-bebas">
                                    <a href="{{ route('public.artist', $featuredArtist) }}">{{ $artistName ?? 'Artiste' }}</a>
                                </h3>
                                <p class="ph-feature__phrase font-cormorant">
                                    {{ $featuredArtist->bio ? Str::limit(strip_tags($featuredArtist->bio), 120) : 'Son atelier reste ouvert toute la nuit.' }}
                                </p>
                                <p class="ph-feature__meta">
                                    <span>{{ Fr::count($artistWorks->count(), 'œuvre') }}</span>
                                    <a href="{{ route('public.artist', $featuredArtist) }}">Entrer dans l'atelier <span aria-hidden="true">→</span></a>
                                </p>
                            </div>
                        </article>
                    @endif

                    @if ($featuredWriter)
                        <article class="glass-card ph-feature ph-feature--lavande">
                            <a href="{{ route('public.writer', $featuredWriter) }}" class="ph-feature__thumb ph-feature__thumb--arch" tabindex="-1" aria-hidden="true">
                                @if ($writerHighlight && $writerHighlight->cover_image_path)
                                    <img src="{{ $writerHighlight->coverThumbnailUrl() }}" alt="" loading="lazy">
                                @else
                                    <span class="ph-feature__initial">{{ Str::upper(Str::substr($writerName ?? '?', 0, 1)) }}</span>
                                @endif
                            </a>
                            <div class="ph-feature__body">
                                <p class="ph-feature__kicker">Plume du jour</p>
                                <h3 class="ph-feature__name font-bebas">
                                    <a href="{{ route('public.writer', $featuredWriter) }}">{{ $writerName ?? 'Écrivain' }}</a>
                                </h3>
                                <p class="ph-feature__phrase font-cormorant">
                                    {{ $featuredWriter->bio ? Str::limit(strip_tags($featuredWriter->bio), 120) : 'Ses histoires attendent qu’on tourne la page.' }}
                                </p>
                                <p class="ph-feature__meta">
                                    <span>{{ Fr::count($writerWorks->count(), 'texte') }}@if ($writerHighlight?->genreLabel()) · {{ $writerHighlight->genreLabel() }}@endif</span>
                                    <a href="{{ route('public.writer', $featuredWriter) }}">Visiter son ciel <span aria-hidden="true">→</span></a>
                                </p>
                            </div>
                        </article>
                    @endif
                </div>
            </section>
        @endif

        {{-- 3. Le fil des traits & des mots --}}
        <section class="w-container ph-section" aria-labelledby="ph-fil-title">
            <div class="ph-section__head">
                <h2 id="ph-fil-title" class="ph-h2">
                    <span class="font-bebas">Le fil</span>
                    <span class="font-cormorant ph-h2__italic">des traits &amp; des mots</span>
                </h2>
                <div class="ph-section__links">
                    <a href="{{ route('public.illustrations') }}" class="pont-btn pont-btn--outline">Galerie</a>
                    <a href="{{ route('public.literary-works') }}" class="pont-btn pont-btn--outline">Bibliothèque</a>
                </div>
            </div>

            @if ($feed->isEmpty())
                <p class="glass-card ph-empty font-cormorant">Le fil est encore silencieux. Les premières œuvres arrivent bientôt.</p>
            @else
                <ul class="ph-feed" role="list">
                    @foreach ($feed as $item)
                        @if ($item instanceof Illustration)
                            @php $by = $item->artist?->user?->name; @endphp
                            <li>
                                <a href="{{ route('public.illustration', $item) }}" class="ph-tile ph-tile--illu">
                                    <span class="ph-tile__media">
                                        <img src="{{ $item->thumbnailUrl() }}" alt="{{ $item->title }}{{ $by ? ' par '.$by : '' }}" loading="lazy">
                                    </span>
                                    <span class="ph-tile__foot">
                                        <span class="ph-tile__title">{{ $item->title }}</span>
                                        @if ($by)<span class="ph-tile__by">{{ $by }}</span>@endif
                                        <span class="ph-tile__type">Illustration</span>
                                    </span>
                                </a>
                            </li>
                        @elseif ($item instanceof Document)
                            @php
                                $by = $item->writer?->user?->name;
                                $excerpt = filled($item->description) ? Str::limit(strip_tags($item->description), 170) : null;
                            @endphp
                            <li>
                                <a href="{{ route('documents.read', $item) }}" class="ph-tile ph-tile--text">
                                    <span class="ph-tile__media ph-tile__page">
                                        <span class="ph-tile__kanji" aria-hidden="true">物語</span>
                                        <span class="ph-tile__excerpt font-cormorant">{{ $excerpt ? '« '.$excerpt.' »' : $item->title }}</span>
                                    </span>
                                    <span class="ph-tile__foot">
                                        <span class="ph-tile__title">{{ $item->title }}</span>
                                        @if ($by)<span class="ph-tile__by">{{ $by }}</span>@endif
                                        <span class="ph-tile__type">Récit{{ $item->genreLabel() ? ' · '.$item->genreLabel() : '' }}</span>
                                    </span>
                                </a>
                            </li>
                        @elseif ($item instanceof \App\Models\ComicChapter && $item->comic)
                            @php
                                $comic = $item->comic;
                                $by = $comic->user?->name;
                                $chapterTitle = $comic->isOneShot() ? $comic->titre : $comic->titre.' — ch. '.$item->numberLabel();
                            @endphp
                            <li>
                                <a href="{{ route('comics.read', [$comic, $item->routeNumber()]) }}" class="ph-tile ph-tile--illu ph-tile--comic">
                                    <span class="ph-tile__media">
                                        @if ($comic->coverUrl())
                                            <img src="{{ $comic->coverUrl() }}" alt="Couverture de « {{ $comic->titre }} »{{ $by ? ' par '.$by : '' }}" loading="lazy">
                                        @else
                                            <span class="ph-tile__kanji" aria-hidden="true">漫画</span>
                                        @endif
                                    </span>
                                    <span class="ph-tile__foot">
                                        <span class="ph-tile__title">{{ $chapterTitle }}</span>
                                        @if ($by)<span class="ph-tile__by">{{ $by }}</span>@endif
                                        <span class="ph-tile__type">{{ $comic->readingKind() }}{{ $comic->isOneShot() ? '' : ' · Nouveau chapitre' }}</span>
                                    </span>
                                </a>
                            </li>
                        @elseif ($item instanceof \App\Models\Animation)
                            @php
                                $by = $item->user?->name;
                                $playsInFeed = $item->isLoop() && ! $item->contenu_sensible;
                            @endphp
                            @if ($playsInFeed)
                                @include('animations.partials.assets')
                            @endif
                            <li>
                                <a href="{{ route('animations.show', $item) }}" class="ph-tile ph-tile--illu ph-tile--anim {{ $item->contenu_sensible ? 'is-sensitive' : '' }}">
                                    <span class="ph-tile__media" data-animation-card-frame>
                                        @if ($item->posterUrl())
                                            <img src="{{ $item->posterUrl() }}" alt="{{ $item->contenu_sensible ? '' : 'Image de « '.$item->titre.' »'.($by ? ' par '.$by : '') }}" loading="lazy">
                                        @else
                                            <span class="ph-tile__kanji" aria-hidden="true">動画</span>
                                        @endif
                                        @if ($playsInFeed)
                                            <video data-animation-card muted loop playsinline preload="none" @if ($item->posterUrl()) poster="{{ $item->posterUrl() }}" @endif aria-hidden="true" tabindex="-1" disablepictureinpicture>
                                                <source src="{{ $item->mp4Url() }}" type="video/mp4">
                                            </video>
                                        @endif
                                        @if ($item->durationLabel())
                                            <span class="ph-tile__badge">{{ $item->durationLabel() }}</span>
                                        @endif
                                    </span>
                                    <span class="ph-tile__foot">
                                        <span class="ph-tile__title">{{ $item->titre }}</span>
                                        @if ($by)<span class="ph-tile__by">{{ $by }}</span>@endif
                                        <span class="ph-tile__type">ANIMATION · {{ $item->modeLabel() }}{{ $item->contenu_sensible ? ' · Contenu sensible' : '' }}</span>
                                    </span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- 4. Le collectif --}}
        <section id="apropos" class="w-container ph-section ph-collectif scroll-mt-24" aria-labelledby="ph-collectif-title">
            <div class="ph-collectif__text">
                <p class="ph-kicker">Le collectif</p>
                <h2 id="ph-collectif-title" class="ph-h2 ph-h2--stack">
                    <span class="font-bebas">Des crayons, des plumes,</span>
                    <span class="font-cormorant ph-h2__italic">une même nuit.</span>
                </h2>
                @if (filled($collectiveText))
                    <div class="ph-collectif__body">
                        {!! nl2br(e($collectiveText)) !!}
                    </div>
                @endif
                <a href="{{ route('public.about') }}" class="ph-link">En savoir plus sur le collectif <span aria-hidden="true">→</span></a>
            </div>

            <aside class="glass-card ph-join" aria-labelledby="ph-join-title">
                <span class="ph-join__kanji w-deco" aria-hidden="true">夢</span>
                <h3 id="ph-join-title" class="ph-join__title">
                    <span class="font-bebas">Tu dessines</span>
                    <span class="font-cormorant ph-h2__italic">ou tu écris ?</span>
                </h3>
                <p>Rejoins le collectif : une enseigne dans la rue des illustrateurs ou une étoile dans le ciel des écrivains t'attend.</p>
                <a href="{{ route('public.about') }}#rejoindre" class="pont-btn pont-btn--rose">Demander à rejoindre</a>
            </aside>
        </section>
    </div>
</x-world-layout>
