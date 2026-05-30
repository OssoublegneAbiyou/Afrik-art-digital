<x-app-layout>
    <div class="mx-auto max-w-6xl px-6 py-12">
        <section class="relative overflow-hidden rounded-2xl border border-[#2f6b4f]/20 bg-white p-8 shadow-sm">
            @if ($writer->banner_path)
                <img src="{{ asset('storage/' . $writer->banner_path) }}" alt="Bannière de {{ $writer->user->name }}" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-white/75"></div>
            @endif
            <div class="relative max-w-3xl">
                <p class="inline-flex rounded-full border border-[#cbe3d3] bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#5e7c69]">
                    Auteur
                </p>

                <h1 class="mt-5 text-4xl font-semibold tracking-[-0.04em] text-[#2f6b4f] md:text-6xl">
                    {{ $writer->user->name }}
                </h1>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-[#53665a]">
                    {{ $writer->bio ?: "Cet auteur n'a pas encore rédigé sa biographie." }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                    @if($writer->instagram)<a href="{{ $writer->instagram }}" target="_blank" class="rounded-full border border-[#2f6b4f]/20 bg-white/90 px-4 py-2 text-[#2f6b4f] shadow-sm">Instagram</a>@endif
                    @if($writer->facebook)<a href="{{ $writer->facebook }}" target="_blank" class="rounded-full border border-[#2f6b4f]/20 bg-white/90 px-4 py-2 text-[#2f6b4f] shadow-sm">Facebook</a>@endif
                    @if($writer->website)<a href="{{ $writer->website }}" target="_blank" class="rounded-full border border-[#2f6b4f]/20 bg-white/90 px-4 py-2 text-[#2f6b4f] shadow-sm">Site web</a>@endif
                </div>
            </div>
        </section>

        <section class="mt-10">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-[#2f6b4f]">Bibliothèque</h2>
                <span class="rounded-full border border-[#2f6b4f]/20 bg-white/80 px-4 py-2 text-sm text-[#53665a] shadow-sm">
                    {{ $writer->documents->count() }} œuvres
                </span>
            </div>

            @if($writer->documents->isNotEmpty())
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($writer->documents as $document)
                        <article class="overflow-hidden rounded-2xl border border-[#2f6b4f]/20 bg-white/90 shadow-sm">
                            <img src="{{ asset('storage/' . $document->cover_image_path) }}" alt="{{ $document->title }}" class="h-64 w-full object-cover">
                            <div class="space-y-3 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-lg font-semibold text-[#2f6b4f]">{{ $document->title }}</p>
                                    <span class="rounded-full bg-[#f3f8f2] px-3 py-1 text-xs font-semibold text-[#5e7c69]">
                                        {{ strtoupper($document->file_type) }}
                                    </span>
                                </div>
                                @if($document->description)
                                    <p class="text-sm leading-6 text-[#53665a]">{{ $document->description }}</p>
                                @endif
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('documents.read', $document) }}" class="inline-flex rounded-full bg-[#2f6b4f] px-4 py-2 text-sm font-semibold text-white shadow-sm">
                                        Lire
                                    </a>
                                    @auth
                                        <form method="POST" action="{{ in_array($document->id, $bookmarkedDocumentIds ?? [], true) ? route('documents.unbookmark', $document) : route('documents.bookmark', $document) }}">
                                            @csrf
                                            @if (in_array($document->id, $bookmarkedDocumentIds ?? [], true))
                                                @method('DELETE')
                                            @endif
                                            <button type="submit" class="rounded-full border border-[#2f6b4f]/20 bg-white px-4 py-2 text-sm font-semibold text-[#2f6b4f] shadow-sm">
                                                {{ in_array($document->id, $bookmarkedDocumentIds ?? [], true) ? 'Retirer' : 'Marquer' }}
                                            </button>
                                        </form>
                                    @endauth
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-6 rounded-2xl border border-[#2f6b4f]/20 bg-white/80 p-6 text-[#53665a]">
                    Cet auteur n'a pas encore publié d'œuvre.
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
