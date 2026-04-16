<nav class="sticky top-0 z-50 border-b border-orange-100/80 bg-white/70 backdrop-blur-xl">
    <div class="mx-auto max-w-6xl px-6">
        <div class="flex h-16 items-center justify-between">
            {{-- Logo / Nom du site --}}
            <div class="flex items-center">
                <a href="{{ route('public.index') }}" class="bg-gradient-to-r from-[#ef476f] via-[#ff7b54] to-[#14b8a6] bg-clip-text text-lg font-semibold text-transparent">
                    Afrik'art Digital
                </a>
            </div>

            {{-- Liens de navigation --}}
            <div class="hidden items-center gap-6 sm:flex">
                <a href="{{ route('public.index') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                    Accueil
                </a>
                <a href="{{ route('public.artists') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                    Illustrateurs
                </a>
                <a href="{{ route('public.writers') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#147d6d]">
                    Ecrivains
                </a>

                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.index') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                            Admin
                        </a>
                    @endif
                    @if (auth()->user()->isWriter())
                        <a href="{{ route('dashboard') }}#bibliotheque" class="text-sm font-medium text-[#7b627f] transition hover:text-[#147d6d]">
                            Ma bibliothèque
                        </a>
                    @elseif (auth()->user()->isVisitor())
                        <a href="{{ route('dashboard') }}#bibliotheque" class="text-sm font-medium text-[#7b627f] transition hover:text-[#147d6d]">
                            Ma bibliothèque
                        </a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                        Mon espace
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                            Déconnexion
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                        Connexion
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-full bg-gradient-to-r from-[#ef476f] via-[#ff7b54] to-[#ffb703] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-orange-200/60 transition hover:-translate-y-0.5">
                            Inscription
                        </a>
                    @endif
                @endauth
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-orange-100 py-3 text-sm text-[#7b627f] sm:hidden">
            <a href="{{ route('public.index') }}" class="font-medium text-[#2b183d]">
                Accueil
            </a>
            <a href="{{ route('public.artists') }}" class="font-medium text-[#7b627f]">
                Illustrateurs
            </a>
            <a href="{{ route('public.writers') }}" class="font-medium text-[#7b627f]">
                Ecrivains
            </a>
            @auth
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.index') }}" class="font-medium text-[#7b627f]">
                        Admin
                    </a>
                @endif
                @if (auth()->user()->isWriter() || auth()->user()->isVisitor())
                    <a href="{{ route('dashboard') }}#bibliotheque" class="font-medium text-[#7b627f]">
                        Ma bibliothèque
                    </a>
                @endif
                <a href="{{ route('dashboard') }}" class="font-medium text-[#7b627f]">
                    Mon espace
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="font-medium text-[#7b627f]">
                        Déconnexion
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="font-medium text-[#7b627f]">
                    Connexion
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="font-medium text-[#ef476f]">
                        Inscription
                    </a>
                @endif
            @endauth
        </div>
    </div>
</nav>
