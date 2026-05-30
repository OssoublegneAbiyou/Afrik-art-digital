<x-app-layout>
    <div
        x-data="{
            readerOpen: false,
            readerUrl: '',
            readerTitle: '',
            readerType: '',
            readerText: '',
            canBookmark: false,
            bookmarkAction: '',
            bookmarkRemove: false,
            loginUrl: '{{ route('login') }}'
        }"
        @keydown.escape.window="readerOpen = false"
        class="mx-auto max-w-6xl px-6 py-12"
    >
        <section class="relative overflow-hidden rounded-[2.2rem] border border-[#1f7a5c]/20 bg-[linear-gradient(135deg,#eef7ed_0%,#fff8ea_58%,#fff8ea_100%)] p-8 shadow-[0_24px_80px_rgba(31,122,92,0.12)]">
            @if ($writer->banner_path)
                <img src="{{ asset('storage/' . $writer->banner_path) }}" alt="Bannière de {{ $writer->user->name }}" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.64),rgba(101,76,141,0.28))]"></div>
            @endif
            <div class="absolute -left-12 top-12 h-32 w-32 rounded-full bg-[#1f7a5c]/20 blur-2xl"></div>
            <div class="absolute right-12 top-8 h-40 w-40 rounded-full bg-[#f5b700]/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 right-0 h-32 bg-[linear-gradient(180deg,transparent,rgba(255,255,255,0.45))]"></div>

            <div class="relative max-w-3xl">
                <p class="inline-flex rounded-full border border-[#cbe3d3] bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#5e7c69]">
                    Auteur
                </p>

                <h1 class="mt-5 text-4xl font-semibold tracking-[-0.04em] text-[#1f7a5c] md:text-6xl">
                    {{ $writer->user->name }}
                </h1>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-[#335247]">
                    {{ $writer->bio ?: "Cet auteur n'a pas encore rédigé sa biographie." }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                    @if($writer->instagram)<a href="{{ $writer->instagram }}" target="_blank" class="rounded-full border border-[#1f7a5c]/20 bg-white/90 px-4 py-2 text-[#1f7a5c] shadow-sm">Instagram</a>@endif
                    @if($writer->facebook)<a href="{{ $writer->facebook }}" target="_blank" class="rounded-full border border-[#1f7a5c]/20 bg-white/90 px-4 py-2 text-[#1f7a5c] shadow-sm">Facebook</a>@endif
                    @if($writer->website)<a href="{{ $writer->website }}" target="_blank" class="rounded-full border border-[#1f7a5c]/20 bg-white/90 px-4 py-2 text-[#1f7a5c] shadow-sm">Site web</a>@endif
                </div>
            </div>
        </section>

        <section class="mt-10">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-[#1f7a5c]">Bibliothèque</h2>
                <span class="rounded-full border border-[#1f7a5c]/20 bg-white/80 px-4 py-2 text-sm text-[#335247] shadow-sm">
                    {{ $writer->documents->count() }} œuvres
                </span>
            </div>

            @if($writer->documents->isNotEmpty())
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($writer->documents as $document)
                        <article class="overflow-hidden rounded-[1.8rem] border border-[#1f7a5c]/20 bg-white/90 shadow-[0_18px_48px_rgba(31,122,92,0.12)]">
                            <img src="{{ asset('storage/' . $document->cover_image_path) }}" alt="{{ $document->title }}" class="h-64 w-full object-cover">
                            <div class="space-y-3 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-lg font-semibold text-[#1f7a5c]">{{ $document->title }}</p>
                                    <span class="rounded-full bg-[#f3f8f2] px-3 py-1 text-xs font-semibold text-[#5e7c69]">
                                        {{ strtoupper($document->file_type) }}
                                    </span>
                                </div>
                                @if($document->description)
                                    <p class="text-sm leading-6 text-[#335247]">{{ $document->description }}</p>
                                @endif
                                <div class="flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        @click="
                                            readerUrl = '{{ asset('storage/' . $document->file_path) }}';
                                            readerTitle = @js($document->title);
                                            readerType = '{{ strtolower($document->file_type) }}';
                                            readerText = @js($document->reader_text);
                                            canBookmark = {{ auth()->check() ? 'true' : 'false' }};
                                            bookmarkAction = '{{ in_array($document->id, $bookmarkedDocumentIds ?? [], true) ? route('documents.unbookmark', $document) : route('documents.bookmark', $document) }}';
                                            bookmarkRemove = {{ in_array($document->id, $bookmarkedDocumentIds ?? [], true) ? 'true' : 'false' }};
                                            readerOpen = true;
                                        "
                                        class="inline-flex rounded-full bg-gradient-to-r from-[#1f7a5c] via-[#f5b700] to-[#f5b700] px-4 py-2 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(126,207,154,0.2)]"
                                    >
                                        Lire ici
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-6 rounded-[1.75rem] border border-[#1f7a5c]/20 bg-white/80 p-6 text-[#335247]">
                    Cet auteur n'a pas encore publié d'œuvre.
                </div>
            @endif
        </section>

        <div
            x-cloak
            x-show="readerOpen"
            x-transition.opacity
            class="fixed inset-0 z-[70] flex items-center justify-center bg-black/80 px-4 py-6"
            @click.self="readerOpen = false"
        >
            <div class="relative w-full max-w-3xl">
                <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.45)]">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#1f7a5c]/20 bg-[#eef7ed] px-5 py-4">
                        <p class="text-lg font-semibold text-[#1f7a5c]" x-text="readerTitle"></p>
                        <div class="flex flex-wrap items-center gap-3">
                            <template x-if="canBookmark">
                                <form method="POST" :action="bookmarkAction" class="shrink-0">
                                    @csrf
                                    <template x-if="bookmarkRemove">
                                        <input type="hidden" name="_method" value="DELETE">
                                    </template>
                                    <button type="submit" class="rounded-full border border-[#1f7a5c]/20 bg-white px-4 py-2 text-xs font-semibold text-[#1f7a5c] shadow-sm">
                                        <span x-text="bookmarkRemove ? 'Retirer le marque-page' : 'Ajouter un marque-page'"></span>
                                    </button>
                                </form>
                            </template>
                            <template x-if="!canBookmark">
                                <a :href="loginUrl" class="shrink-0 rounded-full border border-[#1f7a5c]/20 bg-white px-4 py-2 text-xs font-semibold text-[#1f7a5c] shadow-sm">
                                    Se connecter
                                </a>
                            </template>
                            <button
                                type="button"
                                class="shrink-0 rounded-full border border-white/20 bg-[#17110d] px-4 py-2 text-xs font-semibold text-white shadow-[0_10px_24px_rgba(0,0,0,0.2)] transition hover:bg-[#242424]"
                                @click="readerOpen = false"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>

                    <template x-if="readerType === 'txt' && readerText">
                        <div class="max-h-[70vh] overflow-y-auto bg-white px-6 py-5">
                            <p class="whitespace-pre-wrap text-sm leading-7 text-[#1f7a5c]" x-text="readerText"></p>
                        </div>
                    </template>

                    <template x-if="readerType === 'pdf'">
                        <iframe :src="readerUrl" class="h-[70vh] w-full bg-white"></iframe>
                    </template>

                    <template x-if="readerType !== 'pdf' && (readerType !== 'txt' || !readerText)">
                        <div class="space-y-4 p-6 text-center">
                            <p class="text-sm leading-7 text-[#335247]">
                                Ce format ne peut pas encore être lu comme du texte dans la fenêtre. Utilisez l'aperçu intégré ou le téléchargement.
                            </p>
                            <iframe :src="readerUrl" class="h-[50vh] w-full rounded-2xl border border-[#1f7a5c]/20 bg-white"></iframe>
                            <a :href="readerUrl" target="_blank" class="inline-flex rounded-full bg-gradient-to-r from-[#1f7a5c] via-[#f5b700] to-[#f5b700] px-5 py-3 text-sm font-semibold text-white">
                                Télécharger le document
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
