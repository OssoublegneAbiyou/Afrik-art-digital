<nav class="sticky top-0 z-50 border-b border-white/40 bg-white/78 shadow-[0_10px_34px_rgba(89,57,138,0.08)] backdrop-blur-2xl">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="flex min-h-16 items-center justify-between gap-4 py-3">
            <a href="{{ route('public.index') }}" class="group flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border border-black/10 bg-[#181818] text-sm font-black text-white shadow-[0_12px_26px_rgba(0,0,0,0.14)] transition duration-500 ease-out group-hover:-rotate-6 group-hover:scale-105">
                    AD
                </span>
                <span class="hidden leading-tight sm:block">
                    <span class="block text-base font-black text-[#181818]">Afrik'art Digital</span>
                    <span class="block text-[10px] font-black uppercase tracking-[0.26em] text-[#7a669f]">Babi creative feed</span>
                </span>
            </a>

            <div class="hidden items-center gap-2 md:flex">
                <a href="{{ route('public.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-[#4b4b4b] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-white hover:text-[#181818] hover:shadow-[0_10px_24px_rgba(0,0,0,0.08)]">
                    Accueil
                </a>
                <a href="{{ route('public.artists') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-[#4b4b4b] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-[#181818] hover:text-white hover:shadow-[0_12px_26px_rgba(0,0,0,0.12)]">
                    Illustrateurs
                </a>
                <a href="{{ route('public.writers') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-[#4b4b4b] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-gradient-to-r hover:from-[#7a669f] hover:via-[#c084fc] hover:to-[#ffb703] hover:text-white hover:shadow-[0_12px_26px_rgba(122,102,159,0.18)]">
                    Écrivains
                </a>

                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-[#4b4b4b] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-white hover:text-[#ef476f] hover:shadow-[0_10px_24px_rgba(0,0,0,0.08)]">
                            Admin
                        </a>
                    @endif
                    @if (auth()->user()->isWriter() || auth()->user()->isVisitor())
                        <a href="{{ route('dashboard') }}#bibliotheque" class="rounded-full px-4 py-2 text-sm font-semibold text-[#4b4b4b] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-white hover:text-[#7a669f] hover:shadow-[0_10px_24px_rgba(0,0,0,0.08)]">
                            Ma bibliothèque
                        </a>
                    @endif
                    @if (auth()->user()->isArtist())
                        <a href="{{ route('artist-portfolios.create') }}" class="rounded-full bg-[#181818] px-4 py-2 text-sm font-semibold text-white shadow-[0_12px_26px_rgba(0,0,0,0.12)] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-[#2b2b2b]">
                            Créer mon portfolio
                        </a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="rounded-full border border-black/10 bg-white/80 px-4 py-2 text-sm font-semibold text-[#181818] shadow-sm transition duration-300 ease-out hover:-translate-y-0.5 hover:border-[#c084fc]/40 hover:shadow-[0_12px_26px_rgba(122,102,159,0.14)]">
                        Mon espace
                    </a>
                    <a href="{{ route('profile.edit') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-[#4b4b4b] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-white hover:text-[#181818] hover:shadow-[0_10px_24px_rgba(0,0,0,0.08)]">
                        Profil
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-full px-4 py-2 text-sm font-semibold text-[#9a5a40] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-[#fff7ed] hover:text-[#ef476f]">
                            Déconnexion
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-full bg-[#181818] px-4 py-2 text-sm font-semibold text-white shadow-[0_12px_26px_rgba(0,0,0,0.12)] transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-gradient-to-r hover:from-[#181818] hover:via-[#7a669f] hover:to-[#ffb703]">
                        Connexion
                    </a>
                @endauth
            </div>

            <div class="flex items-center gap-2 md:hidden">
                @guest
                    <a href="{{ route('login') }}" class="rounded-full border border-[#201a16] bg-[#201a16] px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-300 ease-out hover:bg-[#7a669f]">
                        Connexion
                    </a>
                @endguest

                <details class="group relative">
                    <summary class="flex list-none items-center gap-3 rounded-full border border-black/10 bg-white/85 px-4 py-2 text-sm font-semibold text-[#201a16] shadow-sm transition duration-300 ease-out marker:hidden hover:-translate-y-0.5 hover:shadow-[0_10px_24px_rgba(0,0,0,0.08)]">
                        Menu
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#181818] text-white transition duration-300 group-open:rotate-90">
                            <svg class="h-4 w-4 group-open:hidden" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M3 5H17M3 10H17M3 15H17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                            <svg class="hidden h-4 w-4 group-open:block" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 5L15 15M15 5L5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </span>
                    </summary>

                    <div class="origin-top-right absolute right-0 top-[calc(100%+0.75rem)] w-[min(22rem,calc(100vw-2rem))] rounded-[1.5rem] border border-white/55 bg-white/92 p-3 opacity-0 shadow-[0_20px_58px_rgba(89,57,138,0.18)] backdrop-blur-2xl transition duration-300 ease-out group-open:translate-y-0 group-open:opacity-100">
                        <div class="grid gap-2">
                            <a href="{{ route('public.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#201a16] transition duration-300 hover:bg-[#f6f6f6]">
                                Accueil
                            </a>
                            <a href="{{ route('public.artists') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#4b4b4b] transition duration-300 hover:bg-[#181818] hover:text-white">
                                Illustrateurs
                            </a>
                            <a href="{{ route('public.writers') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#4b4b4b] transition duration-300 hover:bg-[#f6efff] hover:text-[#7a669f]">
                                Écrivains
                            </a>

                            @auth
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('admin.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#4b4b4b] transition duration-300 hover:bg-[#fff7ed] hover:text-[#ef476f]">
                                        Admin
                                    </a>
                                @endif
                                @if (auth()->user()->isWriter() || auth()->user()->isVisitor())
                                    <a href="{{ route('dashboard') }}#bibliotheque" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#4b4b4b] transition duration-300 hover:bg-[#f6efff] hover:text-[#7a669f]">
                                        Ma bibliothèque
                                    </a>
                                @endif
                                @if (auth()->user()->isArtist())
                                    <a href="{{ route('artist-portfolios.create') }}" class="rounded-xl bg-[#181818] px-4 py-3 text-sm font-semibold text-white transition duration-300 hover:bg-[#2b2b2b]">
                                        Créer mon portfolio
                                    </a>
                                @endif
                                <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#4b4b4b] transition duration-300 hover:bg-[#f6f6f6] hover:text-[#181818]">
                                    Mon espace
                                </a>
                                <a href="{{ route('profile.edit') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#4b4b4b] transition duration-300 hover:bg-[#f6f6f6] hover:text-[#181818]">
                                    Profil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl border border-[#eadfcb] bg-[#fff7ed] px-4 py-3 text-left text-sm font-semibold text-[#9a5a40] transition duration-300 hover:bg-[#fff0dc] hover:text-[#ef476f]">
                                        Déconnexion
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#4b4b4b] transition duration-300 hover:bg-[#f6f6f6]">
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
