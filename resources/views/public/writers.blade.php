<x-app-layout>
    <section class="mx-auto max-w-6xl px-6 py-14">
        <div class="rounded-2xl border border-[#2f6b4f]/20 bg-white p-8 shadow-sm">
            <span class="inline-flex rounded-full border border-[#2f6b4f]/25 bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#2f6b4f]">
                Écrivains
            </span>
            <h1 class="mt-4 text-3xl font-semibold text-[#2f6b4f] md:text-4xl">Tous les écrivains</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#53665a]">
                Parcours la bibliothèque complète et découvre les plumes mises en ligne sur la plateforme.
            </p>

            @if ($featuredWriter)
                <div class="mt-6 rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#2f6b4f]">Écrivain du jour</p>
                    <div class="mt-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xl font-semibold text-[#2f6b4f]">{{ $featuredWriter->user?->name ?? 'Écrivain' }}</p>
                            <p class="mt-2 text-sm text-[#53665a]">{{ $featuredWriter->bio ?: "Une plume mise en avant aujourd'hui sur Afrik art Digital." }}</p>
                        </div>
                        <a href="{{ route('public.writer', $featuredWriter) }}" class="rounded-full bg-[#2f6b4f] px-5 py-3 text-sm font-semibold text-white shadow-sm">
                            Voir le profil du jour
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($writers as $writer)
                @php($firstDocument = $writer->documents->first())
                <article class="overflow-hidden rounded-2xl border border-[#2f6b4f]/20 bg-white/85 shadow-sm">
                    @if ($firstDocument)
                        <img
                            src="{{ asset('storage/' . $firstDocument->cover_image_path) }}"
                            alt="{{ $firstDocument->title }}"
                            class="h-52 w-full object-cover"
                            loading="lazy"
                        >
                    @endif
                    <div class="space-y-3 p-6">
                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ route('public.writer', $writer) }}" class="text-lg font-semibold text-[#2f6b4f] transition hover:text-[#9a4f2c]">
                                {{ $writer->user?->name ?? 'Écrivain' }}
                            </a>
                            @if ($firstDocument)
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-[#2f6b4f] shadow-sm">
                                    {{ strtoupper($firstDocument->file_type) }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-[#53665a]">
                            {{ $writer->bio ?: 'Une plume à découvrir, entre récit personnel et imaginaire.' }}
                        </p>
                        <div class="flex items-center justify-between text-sm text-[#53665a]">
                            <span>{{ $writer->documents->count() }} œuvre(s)</span>
                            <a href="{{ route('public.writer', $writer) }}" class="font-semibold text-[#9a4f2c]">
                                Voir le profil
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-[#2f6b4f]/20 bg-white/80 p-8 text-center text-[#53665a] shadow-sm sm:col-span-2 lg:col-span-3">
                    Aucun écrivain pour le moment.
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
