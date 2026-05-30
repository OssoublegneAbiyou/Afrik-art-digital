<nav class="sticky top-0 z-50 border-b border-[#f5b700]/25 bg-[#fff8ea]/90 backdrop-blur-xl">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="flex min-h-16 items-center justify-between gap-4 py-3">
            <a href="{{ route('public.index') }}" class="max-w-[12rem] bg-gradient-to-r from-[#17110d] via-[#c84c31] to-[#f5b700] bg-clip-text text-base font-extrabold leading-tight text-transparent sm:max-w-none sm:text-lg">
                Afrik'art Digital
            </a>

            <div class="hidden items-center gap-6 md:flex">
                <a href="{{ route('public.index') }}" class="text-sm font-semibold text-[#594234] transition hover:text-[#c84c31]">
                    Accueil
                </a>
                <a href="{{ route('public.artists') }}" class="text-sm font-semibold text-[#594234] transition hover:text-[#c84c31]">
                    Illustrateurs
                </a>
                <a href="{{ route('public.writers') }}" class="text-sm font-semibold text-[#594234] transition hover:text-[#1f7a5c]">
                    Écrivains
                </a>

                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.index') }}" class="text-sm font-semibold text-[#594234] transition hover:text-[#c84c31]">
                            Admin
                        </a>
                    @endif
                    @if (auth()->user()->isWriter() || auth()->user()->isVisitor())
                        <a href="{{ route('dashboard') }}#bibliotheque" class="text-sm font-semibold text-[#594234] transition hover:text-[#1f7a5c]">
                            Ma bibliothèque
                        </a>
                    @endif
                    @if (auth()->user()->isArtist())
                        <a href="{{ route('artist-portfolios.create') }}" class="text-sm font-semibold text-[#594234] transition hover:text-[#c84c31]">
                            Créer mon portfolio
                        </a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-[#594234] transition hover:text-[#c84c31]">
                        Mon espace
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-[#594234] transition hover:text-[#c84c31]">
                            Déconnexion
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-full bg-[#17110d] px-4 py-2 text-sm font-semibold text-[#fff8ea] shadow-[0_12px_30px_rgba(23,17,13,0.18)] transition hover:-translate-y-0.5 hover:bg-[#c84c31]">
                        Connexion
                    </a>
                @endauth
            </div>

            <div class="flex items-center gap-2 md:hidden">
                @guest
                    <a href="{{ route('login') }}" class="rounded-full border border-[#f5b700]/35 bg-[#17110d] px-4 py-2 text-sm font-semibold text-[#fff8ea] shadow-sm transition hover:bg-[#c84c31]">
                        Connexion
                    </a>
                @endguest

                <details class="group relative">
                    <summary class="flex list-none items-center gap-3 rounded-full border border-[#f5b700]/35 bg-[#fff8ea]/95 px-4 py-2 text-sm font-semibold text-[#17110d] shadow-sm marker:hidden">
                        Menu
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#f5b700]/20 text-[#c84c31]">
                            <svg class="h-4 w-4 group-open:hidden" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M3 5H17M3 10H17M3 15H17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                            <svg class="hidden h-4 w-4 group-open:block" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 5L15 15M15 5L5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </span>
                    </summary>

                    <div class="absolute right-0 top-[calc(100%+0.75rem)] w-[min(22rem,calc(100vw-2rem))] rounded-[1.5rem] border border-[#f5b700]/25 bg-[#fff8ea]/95 p-3 shadow-[0_18px_50px_rgba(23,17,13,0.12)] backdrop-blur-xl">
                        <div class="grid gap-2">
                            <a href="{{ route('public.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#17110d] transition hover:bg-[#f5b700]/15">
                                Accueil
                            </a>
                            <a href="{{ route('public.artists') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#594234] transition hover:bg-[#f5b700]/15">
                                Illustrateurs
                            </a>
                            <a href="{{ route('public.writers') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#594234] transition hover:bg-[#f5b700]/15">
                                Écrivains
                            </a>

                            @auth
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('admin.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#594234] transition hover:bg-[#f5b700]/15">
                                        Admin
                                    </a>
                                @endif
                                @if (auth()->user()->isWriter() || auth()->user()->isVisitor())
                                    <a href="{{ route('dashboard') }}#bibliotheque" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#594234] transition hover:bg-[#f5b700]/15">
                                        Ma bibliothèque
                                    </a>
                                @endif
                                @if (auth()->user()->isArtist())
                                    <a href="{{ route('artist-portfolios.create') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#594234] transition hover:bg-[#f5b700]/15">
                                        Créer mon portfolio
                                    </a>
                                @endif
                                <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#594234] transition hover:bg-[#f5b700]/15">
                                    Mon espace
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl border border-[#c84c31]/20 bg-[#c84c31]/10 px-4 py-3 text-left text-sm font-semibold text-[#c84c31] transition hover:bg-[#c84c31]/15">
                                        Déconnexion
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#594234] transition hover:bg-[#f5b700]/15">
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
