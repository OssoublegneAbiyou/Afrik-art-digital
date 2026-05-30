<x-app-layout>
    <section class="mx-auto max-w-6xl px-6 py-14">
        <div class="rounded-[2rem] border border-[#f5b700]/25 bg-[linear-gradient(135deg,#fff8ea_0%,#fffdf7_48%,#f6e3c0_100%)] p-8 shadow-[0_20px_60px_rgba(200,76,49,0.14)]">
            <span class="inline-flex rounded-full border border-[#f5b700]/35 bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#c84c31]">
                Illustrateurs
            </span>
            <h1 class="mt-4 text-3xl font-semibold text-[#17110d] md:text-4xl">Tous les illustrateurs</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#594234]">
                Explore la scène visuelle complète et découvre de nouveaux univers graphiques.
            </p>

            @if ($featuredArtist)
                <div class="mt-6 rounded-[1.5rem] border border-white/70 bg-white/80 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#c84c31]">Artiste du jour</p>
                    <div class="mt-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xl font-semibold text-[#17110d]">{{ $featuredArtist->user?->name ?? 'Artiste' }}</p>
                            <p class="mt-2 text-sm text-[#594234]">{{ $featuredArtist->bio ?: "Un profil mis en avant aujourd'hui sur Afrik art Digital." }}</p>
                        </div>
                        <a href="{{ route('public.artist', $featuredArtist) }}" class="rounded-full bg-gradient-to-r from-[#c84c31] via-[#c84c31] to-[#f5b700] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-200/60">
                            Voir le profil du jour
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($artists as $artist)
                <a href="{{ route('public.artist', $artist) }}" class="group block overflow-hidden rounded-[1.75rem] border border-white/70 bg-white/80 p-6 shadow-[0_18px_50px_rgba(36,59,107,0.10)] transition hover:-translate-y-1 hover:shadow-[0_24px_70px_rgba(200,76,49,0.16)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-[#17110d] transition group-hover:text-[#c84c31]">
                                {{ $artist->user?->name ?? 'Artiste' }}
                            </h2>
                            <p class="mt-2 text-sm text-[#594234]">
                                {{ $artist->bio ?: 'Une voix visuelle en construction sur Afrik art Digital.' }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full border border-[#f5b700]/25 bg-[#fff7f1] px-3 py-1 text-xs font-semibold text-[#c84c31]">
                            {{ $artist->portfolios->count() }} portfolio(s)
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @forelse ($artist->illustrations->take(4) as $illustration)
                            <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#ffe3d3] to-[#fef6df]">
                                <img
                                    src="{{ asset('storage/' . $illustration->image_path) }}"
                                    alt="{{ $illustration->title }}"
                                    class="h-28 w-full object-cover"
                                    loading="lazy"
                                >
                            </div>
                        @empty
                            <div class="col-span-2 rounded-2xl border border-dashed border-[#f5b700]/35 bg-white/70 px-4 py-8 text-center text-sm text-[#8a4a2f]">
                                Pas encore d'illustration publiée.
                            </div>
                        @endforelse
                    </div>
                    <span class="mt-5 inline-flex rounded-full bg-[#17110d] px-4 py-2 text-xs font-semibold text-white">
                        Voir sa page
                    </span>
                </a>
            @empty
                <div class="rounded-[1.75rem] border border-[#f5b700]/25 bg-white/80 p-8 text-center text-[#594234] shadow-[0_18px_50px_rgba(200,76,49,0.12)] sm:col-span-2 lg:col-span-3">
                    Aucun illustrateur pour le moment.
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
