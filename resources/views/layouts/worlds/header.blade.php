@php
    $links = match ($world) {
        'neo' => [
            ['label' => 'Accueil', 'url' => route('public.index'), 'active' => request()->routeIs('public.index')],
            ['label' => 'Illustrateurs', 'url' => route('public.artists'), 'active' => request()->routeIs('public.artists', 'public.artist')],
            ['label' => 'Galerie', 'url' => route('public.illustrations'), 'active' => request()->routeIs('public.illustrations', 'public.illustration')],
            // Récits dessinés : masqué si la rubrique est désactivée (réglage bd.active).
            ...(Route::has('comics.index') && \App\Http\Middleware\EnsureComicsEnabled::enabled()
                ? [['label' => 'Récits dessinés', 'url' => route('comics.index'), 'active' => request()->routeIs('comics.index', 'comics.show')]]
                : []),
            ['label' => 'Écrivains', 'url' => route('public.writers'), 'active' => request()->routeIs('public.writers', 'public.writer')],
        ],
        'songe' => [
            ['label' => 'Accueil', 'url' => route('public.index'), 'active' => request()->routeIs('public.index')],
            ['label' => 'Illustrateurs', 'url' => route('public.artists'), 'active' => request()->routeIs('public.artists', 'public.artist')],
            ['label' => 'Écrivains', 'url' => route('public.writers'), 'active' => request()->routeIs('public.writers', 'public.writer', 'public.literary-works', 'documents.read')],
        ],
        default => [
            ['label' => 'Accueil', 'url' => route('public.index'), 'active' => request()->routeIs('public.index')],
            ['label' => 'Illustrateurs', 'url' => route('public.artists'), 'active' => request()->routeIs('public.artists')],
            ['label' => 'Écrivains', 'url' => route('public.writers'), 'active' => request()->routeIs('public.writers')],
            ['label' => 'À propos', 'url' => route('public.about'), 'active' => request()->routeIs('public.about')],
        ],
    };
    $subtitle = match ($world) {
        'neo' => null, // dépend du mode, voir plus bas
        'songe' => '· Songe',
        default => $collectif['slogan'] ?? null,
    };
    $user = auth()->user();
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
@endphp

<header class="w-header {{ $overlay ? 'w-header--overlay' : '' }}">
    <div class="w-container w-header__inner">
        <a href="{{ route('public.index') }}" class="w-logo" aria-label="{{ $collectif['nom'] }} — accueil">
            @if ($collectif['logo_url'] ?? null)
                <img src="{{ $collectif['logo_url'] }}" alt="" class="w-logo__img" width="44" height="44">
            @else
                <span class="w-logo__mark" aria-hidden="true">{{ $world === 'songe' ? 'Ad' : 'AD' }}</span>
            @endif
            <span>
                <span class="w-logo__name">{{ $collectif['nom'] }}</span>
                @if ($world === 'neo')
                    <span class="w-logo__sub only-nuit">// NÉO-BABI</span>
                    <span class="w-logo__sub only-jour">// BABI CITY</span>
                @elseif (filled($subtitle))
                    <span class="w-logo__sub">{{ $subtitle }}</span>
                @endif
            </span>
        </a>

        <nav class="w-nav" aria-label="Navigation principale">
            @foreach ($links as $link)
                <a href="{{ $link['url'] }}" class="w-nav__link" @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach

            <a href="{{ route('search') }}" class="w-search" aria-label="Rechercher" @if (request()->routeIs('search')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </a>

            @if ($hasModeToggle)
                @include('layouts.worlds.mode-toggle')
            @endif

            @auth
                <a href="{{ route('notifications.index') }}" class="w-search w-bell" @if (request()->routeIs('notifications.*')) aria-current="page" @endif
                    aria-label="Notifications{{ $unreadCount ? ' ('.$unreadCount.' non lue'.($unreadCount >= 2 ? 's' : '').')' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
                    @if ($unreadCount)
                        <span class="w-bell__count" aria-hidden="true">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>
                <details class="w-account">
                    <summary class="w-nav__cta">{{ $world === 'neo' ? 'MON ESPACE' : 'Mon espace' }} <span aria-hidden="true">▾</span></summary>
                    <nav class="w-menu__panel" aria-label="Mon compte">
                        <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Mon espace</a>
                        <a href="{{ route('collection') }}" @if (request()->routeIs('collection')) aria-current="page" @endif>Ma collection</a>
                        @can('moderate')
                            <a href="{{ route('admin.index') }}" @if (request()->routeIs('admin.*')) aria-current="page" @endif>Admin</a>
                        @endcan
                        <a href="{{ route('profile.edit') }}">Mon compte</a>
                        @if ($user->isVisitor() && setting('adhesion.ouverte', true))
                            <a href="{{ \App\Support\MemberAccounts::joinUrl($user) }}">Publier mes œuvres</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Déconnexion</button>
                        </form>
                    </nav>
                </details>
            @else
                <a href="{{ route('login') }}" class="w-nav__cta" @if (request()->routeIs('login')) aria-current="page" @endif>{{ $world === 'neo' ? 'CONNEXION' : 'Connexion' }}</a>
            @endauth
        </nav>

        <div class="flex items-center gap-2 min-[1100px]:hidden">
            @if ($hasModeToggle)
                @include('layouts.worlds.mode-toggle')
            @endif
            <details class="w-menu">
                <summary>Menu</summary>
                <nav class="w-menu__panel" aria-label="Navigation mobile">
                    @foreach ($links as $link)
                        <a href="{{ $link['url'] }}" @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                    @endforeach
                    @if ($world !== 'pont')
                        <a href="{{ route('public.about') }}">À propos</a>
                    @endif
                    <a href="{{ route('search') }}">Rechercher</a>
                    @auth
                        @can('moderate')
                            <a href="{{ route('admin.index') }}">Admin</a>
                        @endcan
                        <a href="{{ route('dashboard') }}">Mon espace</a>
                        <a href="{{ route('notifications.index') }}">Notifications{{ $unreadCount ? ' ('.$unreadCount.')' : '' }}</a>
                        <a href="{{ route('collection') }}">Ma collection</a>
                        <a href="{{ route('profile.edit') }}">Mon compte</a>
                        @if ($user->isVisitor() && setting('adhesion.ouverte', true))
                            <a href="{{ \App\Support\MemberAccounts::joinUrl($user) }}">Publier mes œuvres</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Déconnexion</button>
                        </form>
                    @else
                        <a href="{{ route('public.about') }}#rejoindre">Rejoindre le collectif</a>
                        <a href="{{ route('login') }}">Connexion</a>
                    @endauth
                </nav>
            </details>
        </div>
    </div>
</header>
