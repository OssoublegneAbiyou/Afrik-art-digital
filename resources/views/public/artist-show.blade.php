@php
    $socialLinks = collect([
        'Instagram' => $artist->instagram,
        'X' => $artist->twitter,
        'Facebook' => $artist->facebook,
        'YouTube' => $artist->youtube,
        'Behance' => $artist->behance,
        'Site web' => $artist->website,
    ])->filter();
@endphp

<x-app-layout>
    <div class="min-h-screen bg-[#fbf7ef] text-[#201a16]">
        <section class="mx-auto max-w-6xl px-6 py-12">
            <div class="relative overflow-hidden rounded-2xl border border-black/10 bg-white p-8 shadow-sm">
                @if ($artist->banner_path)
                    <img src="{{ asset('storage/' . $artist->banner_path) }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-22">
                    <div class="absolute inset-0 bg-white/80"></div>
                @endif

                <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <span class="inline-flex rounded-full border border-[#d7aa45]/35 bg-[#fff7f1] px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-[#9a4f2c]">
                            Illustrateur
                        </span>
                        <h1 class="mt-4 text-4xl font-semibold uppercase tracking-[-0.04em] md:text-6xl">
                            {{ $artist->user?->name ?? 'Artiste' }}
                        </h1>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-[#6a5a4d]">
                            {{ $artist->bio ?: "Cet artiste n'a pas encore rédigé sa biographie." }}
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($socialLinks as $label => $url)
                                <a href="{{ $url }}" target="_blank" rel="noreferrer" class="rounded-full border border-black/10 bg-white px-4 py-2 text-sm font-semibold text-[#201a16] shadow-sm">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @auth
                            <form method="POST" action="{{ $isFollowingArtist ? route('artists.unfollow', $artist) : route('artists.follow', $artist) }}">
                                @csrf
                                @if ($isFollowingArtist)
                                    @method('DELETE')
                                @endif
                                <button type="submit" class="rounded-full bg-[#201a16] px-5 py-3 text-sm font-semibold text-white shadow-sm">
                                    {{ $isFollowingArtist ? 'Ne plus suivre' : 'Suivre cet artiste' }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full bg-[#201a16] px-5 py-3 text-sm font-semibold text-white shadow-sm">
                                Connectez-vous pour suivre
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-6 pb-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold uppercase tracking-[0.08em]">Portfolios immersifs</h2>
                    <p class="mt-2 text-sm text-[#6a5a4d]">Choisis une visite pour entrer dans l'univers de l'artiste.</p>
                </div>
                <span class="rounded-full border border-black/10 bg-white px-4 py-2 text-sm text-[#4b4b4b] shadow-sm">
                    {{ $artist->portfolios->count() }} portfolio(s)
                </span>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($artist->portfolios as $portfolio)
                    @php($coverItem = $portfolio->items->first())
                    <a href="{{ route('artist-portfolios.show', $portfolio) }}" class="group overflow-hidden rounded-2xl border border-black/10 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                        <div class="relative h-56 bg-[#201a16]">
                            @if ($coverItem?->illustration)
                                <img src="{{ asset('storage/' . $coverItem->illustration->image_path) }}" alt="{{ $coverItem->illustration->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="flex h-full items-center justify-center bg-[#201a16] text-sm font-semibold uppercase tracking-[0.24em] text-white/62">
                                    Portfolio
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-black/45"></div>
                            <div class="absolute bottom-4 left-4 right-4 text-white">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/60">{{ $portfolio->items->count() }} scène(s)</p>
                                <h3 class="mt-1 text-xl font-semibold">{{ $portfolio->title }}</h3>
                            </div>
                        </div>
                        <div class="p-5">
                            <p class="text-sm leading-6 text-[#6a5a4d]">
                                {{ $portfolio->description ?: 'Visite immersive composée par l'artiste.' }}
                            </p>
                            <span class="mt-4 inline-flex rounded-full bg-[#201a16] px-4 py-2 text-xs font-semibold text-white">
                                Voir ce portfolio
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#d7aa45]/35 bg-white/80 p-6 text-sm text-[#6a5a4d] md:col-span-2 lg:col-span-3">
                        Cet artiste n'a pas encore créé de portfolio immersif.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-6 pb-16 pt-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold uppercase tracking-[0.08em]">Illustrations</h2>
                    <p class="mt-2 text-sm text-[#6a5a4d]">Toutes les images publiées par l'artiste.</p>
                </div>
                <span class="rounded-full border border-black/10 bg-white px-4 py-2 text-sm text-[#4b4b4b] shadow-sm">
                    {{ $artist->illustrations->count() }} illustration(s)
                </span>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($artist->illustrations as $illustration)
                    <article class="overflow-hidden rounded-[1.4rem] border border-black/10 bg-white shadow-sm">
                        <img src="{{ asset('storage/' . $illustration->image_path) }}" alt="{{ $illustration->title }}" class="h-64 w-full object-cover">
                        <div class="p-4">
                            <h3 class="text-base font-semibold">{{ $illustration->title }}</h3>
                            @auth
                                <form method="POST" action="{{ in_array($illustration->id, $favoriteIllustrationIds, true) ? route('illustrations.unfavorite', $illustration) : route('illustrations.favorite', $illustration) }}" class="mt-3">
                                    @csrf
                                    @if (in_array($illustration->id, $favoriteIllustrationIds, true))
                                        @method('DELETE')
                                    @endif
                                    <button type="submit" class="rounded-full border border-black/10 bg-[#f6f6f6] px-4 py-2 text-xs font-semibold text-[#201a16]">
                                        {{ in_array($illustration->id, $favoriteIllustrationIds, true) ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
                                    </button>
                                </form>
                            @endauth
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#d7aa45]/35 bg-white/80 p-6 text-sm text-[#6a5a4d] sm:col-span-2 lg:col-span-3">
                        Cet artiste n'a pas encore publié d'illustrations.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
