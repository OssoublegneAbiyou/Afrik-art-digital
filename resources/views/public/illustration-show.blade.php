<x-app-layout>
    <section class="mx-auto max-w-6xl px-6 py-14">
        <div class="rounded-2xl border border-[#d7aa45]/25 bg-white p-6 shadow-sm md:p-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_0.85fr] lg:items-center">
                <div class="overflow-hidden rounded-2xl bg-[#efe4d6]">
                    <img
                        src="{{ asset('storage/' . $illustration->image_path) }}"
                        alt="{{ $illustration->title }}"
                        class="max-h-[72vh] w-full bg-[#efe4d6] object-contain"
                    >
                </div>

                <div>
                    <span class="inline-flex rounded-full border border-[#d7aa45]/35 bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#9a4f2c]">
                        Illustration
                    </span>
                    <h1 class="mt-4 text-3xl font-semibold text-[#201a16] md:text-4xl">
                        {{ $illustration->title }}
                    </h1>
                    <a href="{{ route('public.artist', $artist) }}" class="mt-3 inline-flex text-sm font-semibold text-[#9a4f2c] transition hover:text-[#201a16]">
                        {{ $artist->user?->name ?? 'Artiste' }}
                    </a>
                    <p class="mt-4 max-w-xl text-sm leading-7 text-[#6a5a4d]">
                        {{ $artist->bio ?: "Cette œuvre fait partie de la galerie publique de l'artiste." }}
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('public.artist', $artist) }}" class="rounded-full bg-[#201a16] px-5 py-3 text-sm font-semibold text-white shadow-sm">
                            Voir la page de l'illustrateur
                        </a>

                        @auth
                            <form method="POST" action="{{ $isFavorite ? route('illustrations.unfavorite', $illustration) : route('illustrations.favorite', $illustration) }}">
                                @csrf
                                @if ($isFavorite)
                                    @method('DELETE')
                                @endif
                                <button type="submit" class="rounded-full border border-[#d7aa45]/35 bg-white px-5 py-3 text-sm font-semibold text-[#9a4f2c] shadow-sm">
                                    {{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full border border-[#d7aa45]/35 bg-white px-5 py-3 text-sm font-semibold text-[#9a4f2c] shadow-sm">
                                Se connecter pour ajouter aux favoris
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        <section class="mt-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-[#201a16]">Du même artiste</h2>
                    <p class="mt-2 text-sm text-[#6a5a4d]">Continue dans le même univers visuel.</p>
                </div>
                <span class="rounded-full border border-[#d7aa45]/25 bg-white px-4 py-2 text-sm text-[#6a5a4d] shadow-sm">
                    {{ $artistIllustrations->count() }} illustration(s)
                </span>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($artistIllustrations as $artistIllustration)
                    <x-public.illustration-card :illustration="$artistIllustration" />
                @empty
                    <div class="rounded-2xl border border-dashed border-[#d7aa45]/35 bg-white/80 p-6 text-sm text-[#6a5a4d] sm:col-span-2 lg:col-span-4">
                        Cette œuvre est la seule illustration publiée par cet artiste pour le moment.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="mt-10">
            <div>
                <h2 class="text-2xl font-semibold text-[#201a16]">Suggestions</h2>
                <p class="mt-2 text-sm text-[#6a5a4d]">D'autres œuvres à découvrir chez les illustrateurs Afrik'art Digital.</p>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($suggestedIllustrations as $suggestion)
                    <x-public.illustration-card :illustration="$suggestion" />
                @empty
                    <div class="rounded-2xl border border-dashed border-[#d7aa45]/35 bg-white/80 p-6 text-sm text-[#6a5a4d] sm:col-span-2 lg:col-span-4">
                        Pas encore assez d'autres illustrations pour proposer une sélection.
                    </div>
                @endforelse
            </div>
        </section>
    </section>
</x-app-layout>
