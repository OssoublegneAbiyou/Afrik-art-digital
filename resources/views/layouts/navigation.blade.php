<nav class="sticky top-0 z-50 border-b border-orange-100/80 bg-white/80 backdrop-blur-xl">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="flex min-h-16 items-center justify-between gap-4 py-3">
            <a href="{{ route('public.index') }}" class="max-w-[12rem] bg-gradient-to-r from-[#ef476f] via-[#ff7b54] to-[#14b8a6] bg-clip-text text-base font-semibold leading-tight text-transparent sm:max-w-none sm:text-lg">
                Afrik'art Digital
            </a>

            <div class="hidden items-center gap-6 md:flex">
                <a href="{{ route('public.index') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                    Accueil
                </a>
                <a href="{{ route('public.artists') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                    Illustrateurs
                </a>
                <a href="{{ route('public.writers') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#147d6d]">
                    Écrivains
                </a>

                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.index') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                            Admin
                        </a>
                    @endif
                    @if (auth()->user()->isWriter() || auth()->user()->isVisitor())
                        <a href="{{ route('dashboard') }}#bibliotheque" class="text-sm font-medium text-[#7b627f] transition hover:text-[#147d6d]">
                            Ma bibliothèque
                        </a>
                    @endif
                    @if (auth()->user()->isArtist())
                        <a href="{{ route('artist-portfolios.create') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                            Creer mon portfolio
                        </a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                        Mon espace
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                            Deconnexion
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-[#7b627f] transition hover:text-[#ef476f]">
                        Connexion
                    </a>
                @endauth
            </div>

            <div class="flex items-center gap-2 md:hidden">
                @guest
                    <a href="{{ route('login') }}" class="rounded-full border border-orange-200 bg-white/90 px-4 py-2 text-sm font-semibold text-[#7b627f] shadow-sm transition hover:bg-[#fff7f1]">
                        Connexion
                    </a>
                @endguest

                <details class="group relative">
                    <summary class="flex list-none items-center gap-3 rounded-full border border-orange-200 bg-white/90 px-4 py-2 text-sm font-semibold text-[#2b183d] shadow-sm marker:hidden">
                    Menu
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#fff4ec] text-[#ef476f]">
                        <svg class="h-4 w-4 group-open:hidden" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M3 5H17M3 10H17M3 15H17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                        <svg class="hidden h-4 w-4 group-open:block" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 5L15 15M15 5L5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                    </summary>

                    <div class="absolute right-0 top-[calc(100%+0.75rem)] w-[min(22rem,calc(100vw-2rem))] rounded-[1.5rem] border border-orange-100 bg-white/95 p-3 shadow-[0_18px_50px_rgba(0,0,0,0.08)] backdrop-blur-xl">
                        <div class="grid gap-2">
                            <a href="{{ route('public.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#2b183d] transition hover:bg-[#fff7f1]">
                                Accueil
                            </a>
                            <a href="{{ route('public.artists') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#7b627f] transition hover:bg-[#fff7f1]">
                                Illustrateurs
                            </a>
                            <a href="{{ route('public.writers') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#7b627f] transition hover:bg-[#fff7f1]">
                                Écrivains
                            </a>

                            @auth
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('admin.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#7b627f] transition hover:bg-[#fff7f1]">
                                        Admin
                                    </a>
                                @endif
                                @if (auth()->user()->isWriter() || auth()->user()->isVisitor())
                                    <a href="{{ route('dashboard') }}#bibliotheque" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#7b627f] transition hover:bg-[#fff7f1]">
                                        Ma bibliothèque
                                    </a>
                                @endif
                                @if (auth()->user()->isArtist())
                                    <a href="{{ route('artist-portfolios.create') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#7b627f] transition hover:bg-[#fff7f1]">
                                        Creer mon portfolio
                                    </a>
                                @endif
                                <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#7b627f] transition hover:bg-[#fff7f1]">
                                    Mon espace
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl border border-orange-200 bg-[#fff7f1] px-4 py-3 text-left text-sm font-semibold text-[#ef476f] transition hover:bg-[#fff1e8]">
                                        Deconnexion
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#7b627f] transition hover:bg-[#fff7f1]">
                                    Connexion
                                </a>
                            @endauth
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</nav>
