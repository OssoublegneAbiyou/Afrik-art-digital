<x-app-layout>
    <div
        x-data="{ lightboxOpen: false, lightboxImage: '', lightboxTitle: '' }"
        @keydown.escape.window="lightboxOpen = false"
        class="mx-auto max-w-6xl px-6 py-12"
    >
        <section class="relative overflow-hidden rounded-[2.2rem] border border-black/10 bg-[linear-gradient(135deg,#ffffff_0%,#f2f2f2_100%)] p-8 shadow-[0_24px_80px_rgba(0,0,0,0.08)]">
            @if ($artist->banner_path)
                <img src="{{ asset('storage/' . $artist->banner_path) }}" alt="Banniere de {{ $artist->user->name }}" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.7),rgba(22,22,22,0.26))]"></div>
            @endif
            <div class="absolute -left-10 top-10 h-28 w-28 rotate-12 rounded-[2rem] border-2 border-[#ff7b54] bg-white/70"></div>
            <div class="absolute right-10 top-10 h-16 w-16 rounded-full bg-[#14b8a6]"></div>
            <div class="absolute right-28 top-24 h-5 w-24 -rotate-12 bg-[#ffb703]"></div>
            <div class="absolute bottom-10 left-14 h-4 w-20 rotate-6 bg-[#ef476f]"></div>

            <div class="relative max-w-3xl">
                <div class="inline-flex items-center gap-3 rounded-full border border-black/10 bg-[#f6f6f6] px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#4b4b4b]">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#ef476f]"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-[#ffb703]"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-[#14b8a6]"></span>
                    Artiste visuel
                </div>

                <h1 class="mt-5 text-4xl font-semibold uppercase tracking-[-0.05em] text-[#141414] md:text-6xl">
                    {{ $artist->user->name }}
                </h1>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-[#525252]">
                    {{ $artist->bio ?: "Cet artiste n'a pas encore redige sa biographie." }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        <form method="POST" action="{{ $isFollowingArtist ? route('artists.unfollow', $artist) : route('artists.follow', $artist) }}">
                            @csrf
                            @if ($isFollowingArtist)
                                @method('DELETE')
                            @endif
                            <button type="submit" class="rounded-full bg-[#181818] px-5 py-3 text-sm font-semibold text-white shadow-[0_14px_30px_rgba(0,0,0,0.12)]">
                                {{ $isFollowingArtist ? 'Ne plus suivre' : 'Suivre cet artiste' }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full bg-[#181818] px-5 py-3 text-sm font-semibold text-white shadow-[0_14px_30px_rgba(0,0,0,0.12)]">
                            Connectez-vous pour suivre
                        </a>
                    @endauth
                </div>

                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                    @if($artist->instagram)<a href="{{ $artist->instagram }}" target="_blank" class="rounded-full border border-black/10 bg-white px-4 py-2 text-[#141414] shadow-sm">Instagram</a>@endif
                    @if($artist->twitter)<a href="{{ $artist->twitter }}" target="_blank" class="rounded-full border border-black/10 bg-white px-4 py-2 text-[#141414] shadow-sm">X</a>@endif
                    @if($artist->facebook)<a href="{{ $artist->facebook }}" target="_blank" class="rounded-full border border-black/10 bg-white px-4 py-2 text-[#141414] shadow-sm">Facebook</a>@endif
                    @if($artist->youtube)<a href="{{ $artist->youtube }}" target="_blank" class="rounded-full border border-black/10 bg-white px-4 py-2 text-[#141414] shadow-sm">YouTube</a>@endif
                    @if($artist->behance)<a href="{{ $artist->behance }}" target="_blank" class="rounded-full border border-black/10 bg-white px-4 py-2 text-[#141414] shadow-sm">Behance</a>@endif
                    @if($artist->website)<a href="{{ $artist->website }}" target="_blank" class="rounded-full border border-black/10 bg-white px-4 py-2 text-[#141414] shadow-sm">Site web</a>@endif
                </div>
            </div>
        </section>

        <section class="mt-10">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold uppercase tracking-[0.08em] text-[#181818]">Galerie</h2>
                <span class="rounded-full border border-black/10 bg-white px-4 py-2 text-sm text-[#4b4b4b] shadow-sm">
                    {{ $artist->illustrations->count() }} oeuvres
                </span>
            </div>

            @if($artist->illustrations->isNotEmpty())
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($artist->illustrations as $index => $illustration)
                        <article class="overflow-hidden rounded-[1.8rem] border border-black/10 bg-white shadow-[0_18px_48px_rgba(0,0,0,0.06)]">
                            <div class="relative">
                                <button
                                    type="button"
                                    class="block w-full text-left"
                                    @click="
                                        lightboxImage = '{{ asset('storage/' . $illustration->image_path) }}';
                                        lightboxTitle = @js($illustration->title);
                                        lightboxOpen = true;
                                    "
                                >
                                    <img src="{{ asset('storage/' . $illustration->image_path) }}" alt="{{ $illustration->title }}" class="h-64 w-full object-cover transition duration-300 hover:scale-[1.02]">
                                </button>
                                <div class="absolute left-4 top-4 h-3 w-14 rounded-full {{ $index % 3 === 0 ? 'bg-[#ef476f]' : ($index % 3 === 1 ? 'bg-[#ffb703]' : 'bg-[#14b8a6]') }}"></div>
                            </div>
                            <div class="p-5">
                                <p class="text-lg font-semibold text-[#181818]">{{ $illustration->title }}</p>
                                <div class="mt-4">
                                    @auth
                                        <form method="POST" action="{{ in_array($illustration->id, $favoriteIllustrationIds, true) ? route('illustrations.unfavorite', $illustration) : route('illustrations.favorite', $illustration) }}">
                                            @csrf
                                            @if (in_array($illustration->id, $favoriteIllustrationIds, true))
                                                @method('DELETE')
                                            @endif
                                            <button type="submit" class="rounded-full border border-black/10 bg-[#f6f6f6] px-4 py-2 text-sm font-semibold text-[#181818] shadow-sm">
                                                {{ in_array($illustration->id, $favoriteIllustrationIds, true) ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('login') }}" class="rounded-full border border-black/10 bg-[#f6f6f6] px-4 py-2 text-sm font-semibold text-[#181818] shadow-sm">
                                            Connectez-vous pour favoris
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-6 rounded-[1.75rem] border border-black/10 bg-white p-6 text-[#525252]">
                    Cet artiste n'a pas encore publie d'illustrations.
                </div>
            @endif
        </section>

        <div
            x-cloak
            x-show="lightboxOpen"
            x-transition.opacity
            class="fixed inset-0 z-[70] flex items-center justify-center bg-black/88 px-4 py-6"
            @click.self="lightboxOpen = false"
        >
            <div class="relative w-full max-w-5xl">
                <button
                    type="button"
                    class="absolute right-0 top-0 z-10 rounded-full bg-white/12 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20"
                    @click="lightboxOpen = false"
                >
                    Fermer
                </button>

                <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-[#111111] shadow-[0_24px_80px_rgba(0,0,0,0.45)]">
                    <img
                        :src="lightboxImage"
                        :alt="lightboxTitle"
                        class="max-h-[78vh] w-full object-contain bg-[#111111]"
                    >
                    <div class="border-t border-white/10 px-5 py-4 text-white">
                        <p class="text-lg font-semibold" x-text="lightboxTitle"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
