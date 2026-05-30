<x-app-layout>
    <section class="mx-auto max-w-6xl px-6 py-14">
        <div class="rounded-[2rem] border border-[#1f7a5c]/20 bg-[linear-gradient(135deg,#eef7ed_0%,#fff8ea_52%,#fff8ea_100%)] p-8 shadow-[0_20px_60px_rgba(31,122,92,0.14)]">
            <span class="inline-flex rounded-full border border-[#1f7a5c]/25 bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#1f7a5c]">
                Écrivains
            </span>
            <h1 class="mt-4 text-3xl font-semibold text-[#1f7a5c] md:text-4xl">Tous les écrivains</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#335247]">
                Parcours la bibliothèque complète et découvre les plumes mises en ligne sur la plateforme.
            </p>

            @if ($featuredWriter)
                <div class="mt-6 rounded-[1.5rem] border border-white/70 bg-white/80 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#1f7a5c]">Écrivain du jour</p>
                    <div class="mt-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xl font-semibold text-[#1f7a5c]">{{ $featuredWriter->user?->name ?? 'Écrivain' }}</p>
                            <p class="mt-2 text-sm text-[#335247]">{{ $featuredWriter->bio ?: "Une plume mise en avant aujourd'hui sur Afrik art Digital." }}</p>
                        </div>
                        <a href="{{ route('public.writer', $featuredWriter) }}" class="rounded-full bg-gradient-to-r from-[#1f7a5c] via-[#1f7a5c] to-[#f5b700] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-100">
                            Voir le profil du jour
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($writers as $writer)
                @php($firstDocument = $writer->documents->first())
                <article class="overflow-hidden rounded-[1.75rem] border border-[#1f7a5c]/20 bg-white/85 shadow-[0_18px_50px_rgba(31,122,92,0.12)]">
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
                            <a href="{{ route('public.writer', $writer) }}" class="text-lg font-semibold text-[#1f7a5c] transition hover:text-[#c84c31]">
                                {{ $writer->user?->name ?? 'Écrivain' }}
                            </a>
                            @if ($firstDocument)
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-[#1f7a5c] shadow-sm">
                                    {{ strtoupper($firstDocument->file_type) }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-[#335247]">
                            {{ $writer->bio ?: 'Une plume à découvrir, entre récit personnel et imaginaire.' }}
                        </p>
                        <div class="flex items-center justify-between text-sm text-[#335247]">
                            <span>{{ $writer->documents->count() }} œuvre(s)</span>
                            <a href="{{ route('public.writer', $writer) }}" class="font-semibold text-[#c84c31]">
                                Voir le profil
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[1.75rem] border border-[#1f7a5c]/20 bg-white/80 p-8 text-center text-[#335247] shadow-[0_18px_50px_rgba(31,122,92,0.12)] sm:col-span-2 lg:col-span-3">
                    Aucun écrivain pour le moment.
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
