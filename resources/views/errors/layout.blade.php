{{--
    Pages d'erreur « Le Pont » — volontairement AUTONOMES : CSS inline + Google Fonts,
    aucun @vite, aucun composant, aucune route() ni accès base, pour s'afficher même si l'application est cassée.
--}}
@php
    // Nom et slogan du collectif (réglages) ; valeurs de repli si la base est indisponible.
    // ENT_COMPAT : guillemets doubles échappés, apostrophe gardée telle quelle.
    $errName = htmlspecialchars((string) ($collectif['nom'] ?? "Afrik'art Digital"), ENT_COMPAT, 'UTF-8');
    $errSlogan = htmlspecialchars((string) ($collectif['slogan'] ?? ''), ENT_COMPAT, 'UTF-8');
    $errCity = htmlspecialchars((string) ($collectif['ville'] ?? ''), ENT_COMPAT, 'UTF-8');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0B0A12">
    <title>@yield('code') · @yield('title') | {!! $errName !!}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Noto+Sans+JP:wght@900&family=Cormorant+Garamond:ital,wght@0,600;1,500;1,600&family=Nunito+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box}
        html,body{margin:0;overflow-x:hidden}
        body{min-height:100vh;min-height:100svh;font-family:'Nunito Sans',system-ui,sans-serif;color:#FFF6EC;
            background:linear-gradient(160deg,#FFE9DC 0%,#E9DDFF 45%,#B9C6EE 100%);display:flex;flex-direction:column}
        a{color:inherit}
        :focus-visible{outline:3px solid #FF2E88;outline-offset:2px}
        .skip{position:absolute;left:12px;top:-60px;z-index:10;padding:10px 16px;border-radius:8px;background:#FFF6EC;color:#0B0A12}
        .skip:focus{top:12px}
        .deco{pointer-events:none;user-select:none}

        .scene{position:relative;flex:1;display:grid;grid-template-columns:minmax(0,1fr)}
        .night{position:relative;overflow:hidden;background:#0B0A12;padding:20px clamp(16px,5vw,64px) 56px;display:flex;flex-direction:column}
        .night::before{content:"";position:absolute;width:520px;height:520px;left:-160px;bottom:-200px;border-radius:50%;background:rgba(255,46,136,.22);filter:blur(80px)}
        .night::after{content:"";position:absolute;width:420px;height:420px;right:-120px;top:-160px;border-radius:50%;background:rgba(46,242,255,.14);filter:blur(80px)}
        .night>*{position:relative;z-index:1}
        .brand{display:inline-flex;align-items:center;gap:12px;min-height:44px;text-decoration:none;color:#FFF6EC;align-self:flex-start}
        .mark{display:grid;place-items:center;width:44px;height:44px;border:2px solid #FF2E88;border-radius:12px;font-family:'Bebas Neue',sans-serif;font-size:1.35rem;
            box-shadow:0 0 14px rgba(255,46,136,.55),inset 0 0 10px rgba(255,46,136,.35)}
        .brand b{display:block;font-family:'Bebas Neue',sans-serif;font-weight:400;font-size:1.4rem;letter-spacing:.04em;line-height:1}
        .brand i{display:block;font-family:'Cormorant Garamond',serif;font-size:.95rem;color:#C9B8FF}
        .night-body{flex:1;display:flex;flex-direction:column;justify-content:center;max-width:36rem;padding-top:24px}
        .code-row{display:flex;align-items:flex-start;gap:clamp(14px,3vw,28px)}
        .code{margin:0;font-family:'Bebas Neue',sans-serif;font-weight:400;line-height:.85;font-size:clamp(7rem,22vw,13.5rem);letter-spacing:.02em;color:#FF7DB5;
            text-shadow:0 0 6px #FF2E88,0 0 20px #FF2E88,0 0 48px rgba(255,46,136,.75),0 0 90px rgba(255,46,136,.45);animation:flick 4s infinite}
        .banner{writing-mode:vertical-rl;text-orientation:upright;letter-spacing:.14em;padding:14px 9px;margin-top:10px;border-radius:6px;
            background:#FFE14D;color:#1A1033;font-family:'Noto Sans JP',sans-serif;font-weight:900;font-size:clamp(1.3rem,3vw,1.8rem);line-height:1;
            box-shadow:0 0 26px rgba(255,225,77,.55);animation:hum 5s ease-in-out infinite}
        .kicker{margin:22px 0 0;font-size:.78rem;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:#2EF2FF;text-shadow:0 0 12px rgba(46,242,255,.45)}
        h1{margin:10px 0 0;font-family:'Bebas Neue',sans-serif;font-weight:400;font-size:clamp(2.2rem,5.4vw,3.6rem);line-height:.95;letter-spacing:.02em;text-transform:uppercase}
        .msg{margin:14px 0 0;max-width:30rem;font-size:1.06rem;line-height:1.7;color:#D9D0F2}
        .skyline{position:absolute;left:0;right:0;bottom:0;width:100%;height:90px;z-index:0}

        .dream{position:relative;overflow:hidden;color:#2A2350;padding:clamp(40px,6vw,72px) clamp(16px,5vw,64px) 28px;display:flex;flex-direction:column}
        .dream-body{flex:1;display:flex;flex-direction:column;justify-content:center;position:relative;z-index:1}
        .moon{position:absolute;right:10%;top:9%;width:clamp(70px,10vw,120px);aspect-ratio:1;border-radius:50%;
            background:radial-gradient(circle at 36% 32%,#FFF3E8 0%,#FFC9A8 55%,#F2A487 100%);box-shadow:0 0 50px rgba(242,164,135,.6);animation:drift 8s ease-in-out infinite}
        .cloud{position:absolute;border-radius:999px;background:rgba(255,255,255,.75);filter:blur(3px);animation:drift 10s ease-in-out infinite}
        .c1{width:200px;height:46px;left:34%;top:20%}
        .c2{width:150px;height:36px;right:18%;bottom:24%;animation-delay:-4s}
        /* Dans le flux (et non en absolu) : elle ne peut plus chevaucher les liens ni le pied de page. */
        .pill-deco{align-self:flex-start;margin-bottom:18px;padding:6px 16px;border-radius:999px;background:rgba(255,246,236,.85);border:1.5px solid #C9B8FF;
            font-family:'Cormorant Garamond',serif;font-style:italic;font-weight:600;font-size:1.15rem;color:#4A4270;animation:drift 9s ease-in-out infinite}
        h2{margin:0;font-family:'Cormorant Garamond',serif;font-style:italic;font-weight:500;font-size:clamp(2.4rem,6vw,4.2rem);line-height:1;color:#2A2350}
        .dream p{margin:12px 0 0;color:#4A4270;font-size:1rem;line-height:1.6;max-width:26rem}
        .paths{list-style:none;margin:26px 0 0;padding:0;display:flex;flex-direction:column;align-items:flex-start;gap:12px}
        .pill{display:inline-flex;align-items:center;gap:10px;min-height:48px;padding:0 24px;border-radius:999px;text-decoration:none;
            font-family:'Cormorant Garamond',serif;font-style:italic;font-weight:600;font-size:1.3rem;
            border:1.5px solid #2A2350;color:#2A2350;background:rgba(255,246,236,.55);transition:transform .2s,background-color .2s}
        .pill:hover{transform:translateX(4px);background:#FFF6EC}
        .pill--main{background:#2A2350;color:#FFF6EC}
        .pill--main:hover{background:#3A2F6E}
        .pill span{font-family:'Nunito Sans',sans-serif;font-style:normal;font-size:1rem}
        footer{position:relative;z-index:1;margin-top:auto;padding-top:40px;display:flex;flex-wrap:wrap;gap:0 16px;align-items:center;font-size:.85rem;color:#4A4270}
        footer a{display:inline-flex;align-items:center;min-height:44px;color:#2A2350;font-weight:700}

        @media (min-width:880px){
            .scene{grid-template-columns:minmax(0,1.1fr) minmax(0,1fr)}
            .night{clip-path:polygon(0 0,100% 0,90% 100%,0 100%);padding-right:clamp(48px,8vw,120px);padding-bottom:170px;min-height:100vh}
            .dream{margin-left:-6vw;padding-left:calc(6vw + clamp(16px,4vw,56px));min-height:100vh}
            .skyline{height:140px}
        }
        /* La skyline (90 px) ne doit jamais passer sous le texte. */
        @media (max-width:879px){ .c1{display:none} .night{padding-bottom:120px} }

        @keyframes flick{0%,92%,100%{opacity:1}93%{opacity:.35}95%{opacity:1}97%{opacity:.5}}
        @keyframes hum{0%,100%{opacity:1;filter:blur(0)}50%{opacity:.82;filter:blur(.4px)}}
        @keyframes drift{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
        @media (prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}}
    </style>
</head>
<body>
    <a href="#contenu" class="skip">Aller au contenu</a>
    <div class="scene">
        <section class="night" aria-labelledby="titre">
            <a href="/" class="brand" aria-label="{!! $errName !!} — accueil">
                <span class="mark" aria-hidden="true">AD</span>
                <span><b>{!! $errName !!}</b>@if ($errSlogan !== '')<i>{!! $errSlogan !!}</i>@endif</span>
            </a>
            <div class="night-body" id="contenu">
                <div class="code-row">
                    <p class="code" aria-hidden="true">@yield('code')</p>
                    <span class="banner deco" aria-hidden="true">迷子</span>
                </div>
                <p class="kicker">Erreur @yield('code') · @yield('title')</p>
                <h1 id="titre">@yield('heading')</h1>
                <p class="msg">@yield('message')</p>
            </div>
            <svg class="skyline deco" aria-hidden="true" focusable="false" viewBox="0 0 800 140" preserveAspectRatio="xMidYMax slice">
                <g fill="#1B1436">
                    <rect x="0" y="70" width="70" height="70"/><rect x="74" y="40" width="44" height="100"/><rect x="124" y="84" width="80" height="56"/>
                    <rect x="210" y="24" width="50" height="116"/><rect x="232" y="6" width="5" height="20"/><rect x="266" y="64" width="90" height="76"/>
                    <rect x="362" y="44" width="56" height="96"/><rect x="424" y="78" width="96" height="62"/><rect x="526" y="30" width="60" height="110"/>
                    <rect x="592" y="70" width="84" height="70"/><rect x="682" y="50" width="54" height="90"/><rect x="742" y="80" width="58" height="60"/>
                </g>
                <g>
                    <rect x="88" y="58" width="7" height="9" fill="#FF2E88"/><rect x="224" y="46" width="7" height="9" fill="#2EF2FF"/>
                    <rect x="380" y="64" width="7" height="9" fill="#FFE14D"/><rect x="544" y="52" width="7" height="9" fill="#FF2E88"/>
                    <rect x="700" y="68" width="7" height="9" fill="#2EF2FF"/><rect x="300" y="86" width="7" height="9" fill="#FFE14D"/>
                </g>
            </svg>
        </section>

        <section class="dream" aria-labelledby="chemin">
            <span class="moon deco" aria-hidden="true"></span>
            <span class="cloud c1 deco" aria-hidden="true"></span>
            <span class="cloud c2 deco" aria-hidden="true"></span>
            <div class="dream-body">
                <span class="pill-deco deco" aria-hidden="true">il était une fois…</span>
                <h2 id="chemin">Reprendre le chemin…</h2>
                <p>Trois passages restent ouverts, même au cœur de la nuit.</p>
                <ul class="paths">
                    <li><a href="/" class="pill pill--main">Retour à l'accueil <span aria-hidden="true">→</span></a></li>
                    <li><a href="/illustrateurs" class="pill">La rue des illustrateurs <span aria-hidden="true">→</span></a></li>
                    <li><a href="/ecrivains" class="pill">Le ciel des écrivains <span aria-hidden="true">→</span></a></li>
                </ul>
            </div>
            <footer>
                <span>© {{ date('Y') }} {!! $errName !!}{!! $errCity !== '' ? ' — '.$errCity : '' !!}</span>
                <a href="/a-propos">À propos</a>
                <a href="/mentions-legales">Mentions légales</a>
            </footer>
        </section>
    </div>
</body>
</html>
