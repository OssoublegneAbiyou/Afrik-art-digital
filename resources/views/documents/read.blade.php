<x-app-layout>
    <div class="min-h-screen bg-[#f7f1e8] text-[#201a16]">
        <section class="border-b border-[#e4d8ca] bg-[#fffdf8]">
            <div class="mx-auto grid max-w-6xl gap-8 px-6 py-10 lg:grid-cols-[12rem_1fr] lg:items-end">
                <img src="{{ $coverUrl }}" alt="{{ $document->title }}" class="h-64 w-48 rounded-2xl object-cover shadow-sm">

                <div>
                    <a href="{{ route('public.writer', $writer) }}" class="text-sm font-semibold text-[#2f6b4f]">
                        {{ $writer->user?->name ?? 'Auteur' }}
                    </a>
                    <h1 class="mt-3 max-w-3xl text-3xl font-bold leading-tight md:text-5xl">
                        {{ $document->title }}
                    </h1>
                    @if ($document->description)
                        <p class="mt-4 max-w-3xl text-sm leading-7 text-[#6a5a4d]">
                            {{ $document->description }}
                        </p>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-3">
                        @auth
                            <form method="POST" action="{{ $isBookmarked ? route('documents.unbookmark', $document) : route('documents.bookmark', $document) }}">
                                @csrf
                                @if ($isBookmarked)
                                    @method('DELETE')
                                @endif
                                <button type="submit" class="rounded-full border border-[#2f6b4f]/25 bg-white px-5 py-3 text-sm font-semibold text-[#2f6b4f] shadow-sm">
                                    {{ $isBookmarked ? 'Retirer le marque-page' : 'Ajouter un marque-page' }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full border border-[#2f6b4f]/25 bg-white px-5 py-3 text-sm font-semibold text-[#2f6b4f] shadow-sm">
                                Se connecter pour marquer
                            </a>
                        @endauth

                        <a href="{{ $fileUrl }}" target="_blank" rel="noreferrer" class="rounded-full bg-[#201a16] px-5 py-3 text-sm font-semibold text-white shadow-sm">
                            Télécharger
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-4xl px-6 py-10">
            @if ($readerText)
                <article class="rounded-2xl border border-[#e4d8ca] bg-[#fffdf8] px-6 py-8 shadow-sm md:px-12 md:py-12">
                    <div class="space-y-6 text-[1.08rem] leading-9 text-[#2c2520]">
                        @foreach (preg_split("/\R{2,}/", trim($readerText)) as $paragraph)
                            @if (trim($paragraph) !== '')
                                <p>{{ trim($paragraph) }}</p>
                            @endif
                        @endforeach
                    </div>
                </article>
            @elseif (strtolower($document->file_type) === 'pdf')
                <div class="overflow-hidden rounded-2xl border border-[#e4d8ca] bg-white shadow-sm">
                    <iframe src="{{ $fileUrl }}" title="{{ $document->title }}" class="h-[82vh] w-full bg-white"></iframe>
                </div>
            @else
                <div class="rounded-2xl border border-[#e4d8ca] bg-white p-8 text-center shadow-sm">
                    <h2 class="text-2xl font-bold text-[#201a16]">Lecture intégrée indisponible</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[#6a5a4d]">
                        Ce fichier est au format {{ strtoupper($document->file_type) }}. Pour le lire, téléchargez-le ou demandez à l'auteur de publier une version TXT, DOCX ou PDF.
                    </p>
                    <a href="{{ $fileUrl }}" target="_blank" rel="noreferrer" class="mt-6 inline-flex rounded-full bg-[#201a16] px-5 py-3 text-sm font-semibold text-white shadow-sm">
                        Télécharger le document
                    </a>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
