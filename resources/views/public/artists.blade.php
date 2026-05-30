<x-app-layout>
    <section class="mx-auto max-w-6xl px-6 py-14">
        <div class="rounded-2xl border border-[#d7aa45]/25 bg-white p-8 shadow-sm">
            <span class="inline-flex rounded-full border border-[#d7aa45]/35 bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#9a4f2c]">
                Illustrateurs
            </span>
            <h1 class="mt-4 text-3xl font-semibold text-[#201a16] md:text-4xl">Tous les illustrateurs</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6a5a4d]">
                Explore la scène visuelle complète et découvre de nouveaux univers graphiques.
            </p>

            @if ($featuredArtist)
                <div class="mt-6 rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#9a4f2c]">Artiste du jour</p>
                    <div class="mt-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xl font-semibold text-[#201a16]">{{ $featuredArtist->user?->name ?? 'Artiste' }}</p>
                            <p class="mt-2 text-sm text-[#6a5a4d]">{{ $featuredArtist->bio ?: "Un profil mis en avant aujourd'hui sur Afrik art Digital." }}</p>
                        </div>
                        <a href="{{ route('public.artist', $featuredArtist) }}" class="rounded-full bg-[#9a4f2c] px-5 py-3 text-sm font-semibold text-white shadow-sm">
                            Voir le profil du jour
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($artists as $artist)
                <a href="{{ route('public.artist', $artist) }}" class="group block overflow-hidden rounded-2xl border border-white/70 bg-white/80 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-[#201a16] transition group-hover:text-[#9a4f2c]">
                                {{ $artist->user?->name ?? 'Artiste' }}
                            </h2>
                            <p class="mt-2 text-sm text-[#6a5a4d]">
                                {{ $artist->bio ?: 'Une voix visuelle en construction sur Afrik art Digital.' }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full border border-[#d7aa45]/25 bg-[#fff7f1] px-3 py-1 text-xs font-semibold text-[#9a4f2c]">
                            {{ $artist->portfolios->count() }} portfolio(s)
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @forelse ($artist->illustrations->take(4) as $illustration)
                            <div class="overflow-hidden rounded-2xl bg-[#efe4d6]">
                                <img
                                    src="{{ asset('storage/' . $illustration->image_path) }}"
                                    alt="{{ $illustration->title }}"
                                    class="h-28 w-full object-cover"
                                    loading="lazy"
                                >
                            </div>
                        @empty
                            <div class="col-span-2 rounded-2xl border border-dashed border-[#d7aa45]/35 bg-white/70 px-4 py-8 text-center text-sm text-[#8a4a2f]">
                                Pas encore d'illustration publiée.
                            </div>
                        @endforelse
                    </div>
                    <span class="mt-5 inline-flex rounded-full bg-[#201a16] px-4 py-2 text-xs font-semibold text-white">
                        Voir sa page
                    </span>
                </a>
            @empty
                <div class="rounded-2xl border border-[#d7aa45]/25 bg-white/80 p-8 text-center text-[#6a5a4d] shadow-sm sm:col-span-2 lg:col-span-3">
                    Aucun illustrateur pour le moment.
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
