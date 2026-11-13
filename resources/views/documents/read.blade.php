@php
    $genre = $document->knownGenre();
    $authorName = $writer->user?->name ?? 'Auteur';
    $hasText = count($chapters) > 0;
    $titledChapters = collect($chapters)->filter(fn ($chapter) => $chapter['title'] !== null)->values();
    $hasToc = $titledChapters->count() >= 2;
    $firstParagraphDone = false;
    $readerConfig = [
        'id' => $document->id,
        'progressUrl' => auth()->check() && $hasText ? route('documents.progress', $document) : null,
        'saved' => $savedProgress ? ['progress' => $savedProgress->progress, 'chapter' => $savedProgress->chapter] : null,
    ];
@endphp

<x-world-layout
    world="songe"
    :title="$document->title.' – '.$authorName"
    :description="$document->description ?: 'Lire « '.$document->title.' » '.\App\Support\Fr::de($writer->user?->name ?? 'un auteur du collectif').' sur '.$collectif['nom'].'.'"
    :image="$coverUrl"
>
    <x-slot name="head">
        <script>
            document.addEventListener('alpine:init', function () {
                window.Alpine.data('songeReader', function (config) {
                    var storageKey = 'aad-reader-pos-' + config.id;

                    return {
                        ambiance: 'jour',
                        size: 19,
                        progress: 0,
                        chapter: 1,
                        resumeAt: null,
                        dirty: false,
                        lastSave: 0,
                        init() {
                            this.ambiance = this.$el.dataset.ambiance || 'jour';
                            this.size = parseInt(getComputedStyle(this.$el).getPropertyValue('--reader-size'), 10) || 19;

                            // Position mémorisée : celle du compte en priorité, sinon celle de ce navigateur.
                            var saved = config.saved;
                            if (! saved) {
                                try { saved = JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch (e) {}
                            }
                            if (saved && saved.progress > 0.03 && saved.progress < 0.97) this.resumeAt = saved;

                            this.measure();
                            var self = this;
                            window.addEventListener('pagehide', function () { self.save(true); });
                            document.addEventListener('visibilitychange', function () { if (document.hidden) self.save(true); });
                        },
                        setAmbiance(a) {
                            this.ambiance = a;
                            try { localStorage.setItem('aad-reader-ambiance', a); } catch (e) {}
                        },
                        resize(delta) {
                            this.size = Math.min(26, Math.max(16, this.size + delta));
                            try { localStorage.setItem('aad-reader-size', String(this.size)); } catch (e) {}
                        },
                        bounds() {
                            var el = this.$refs.text;
                            if (! el) return { start: 0, length: document.documentElement.scrollHeight - window.innerHeight };
                            return { start: el.getBoundingClientRect().top + window.scrollY - window.innerHeight * 0.4, length: el.offsetHeight };
                        },
                        measure() {
                            var b = this.bounds();
                            this.progress = b.length > 0 ? Math.min(1, Math.max(0, (window.scrollY - b.start) / b.length)) : 0;
                            var current = 1;
                            document.querySelectorAll('[data-chapter]').forEach(function (section) {
                                if (section.getBoundingClientRect().top < 140) current = parseInt(section.dataset.chapter, 10);
                            });
                            this.chapter = current;
                        },
                        onScroll() {
                            this.measure();
                            this.dirty = true;
                            if (Date.now() - this.lastSave > 4000) this.save(false);
                        },
                        save(final) {
                            if (! this.dirty || ! this.$refs.text) return;
                            this.dirty = false;
                            this.lastSave = Date.now();
                            var payload = { progress: Math.round(this.progress * 10000) / 10000, chapter: this.chapter };
                            try { localStorage.setItem(storageKey, JSON.stringify(payload)); } catch (e) {}
                            if (! config.progressUrl) return;
                            fetch(config.progressUrl, {
                                method: 'POST',
                                keepalive: final,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify(payload),
                            }).catch(function () {});
                        },
                        resume() {
                            var b = this.bounds();
                            window.scrollTo({ top: Math.max(0, b.start + this.resumeAt.progress * b.length), behavior: 'smooth' });
                            this.resumeAt = null;
                        },
                    };
                });
            });
        </script>
    </x-slot>

    @unless ($document->isVisible())
        <p class="preview-banner" role="status">
            {{ $document->statusLabel() }}{{ $document->isRejected() && $document->moderation_note ? ' ('.$document->moderation_note.')' : '' }} : cette page n'est visible que par toi et l'équipe.
            <a href="{{ route('documents.edit', $document) }}">Modifier</a>
        </p>
    @endunless

    <div class="sg-reader" data-ambiance="jour" style="--reader-size: 19px;"
        x-data="songeReader(@js($readerConfig))" :data-ambiance="ambiance" :style="'--reader-size: ' + size + 'px'"
        @scroll.window.passive="onScroll" @resize.window.passive="measure">
        {{-- Ambiance et taille mémorisées, appliquées avant l'affichage. --}}
        <script>
            (function () {
                var r = document.currentScript.parentElement;
                try {
                    var a = localStorage.getItem('aad-reader-ambiance');
                    if (a === 'jour' || a === 'crepuscule' || a === 'nuit') r.dataset.ambiance = a;
                    else if (document.body.dataset.mode === 'nuit') r.dataset.ambiance = 'nuit';
                    var s = parseInt(localStorage.getItem('aad-reader-size'), 10);
                    if (s >= 16 && s <= 26) r.style.setProperty('--reader-size', s + 'px');
                } catch (e) {}
            })();
        </script>

        {{-- Barre collante --}}
        <div class="sg-reader__bar">
            <div class="w-container sg-reader__bar-inner">
                <a href="{{ route('public.writer', $writer) }}" class="sg-reader__back" aria-label="Retour à {{ $authorName }}">
                    <span aria-hidden="true">←</span>
                    <span class="sg-reader__back-name">{{ $authorName }}</span>
                </a>
                <p class="sg-reader__bar-title">{{ $document->title }}</p>
                <div class="sg-reader__tools">
                    @if ($hasText)
                        <span class="sg-reader__percent" aria-hidden="true" x-text="Math.round(progress * 100) + ' %'"></span>
                    @endif
                    @if ($hasToc)
                        <details class="sg-toc">
                            <summary>Sommaire</summary>
                            <nav class="sg-toc__panel" aria-label="Sommaire">
                                <ol>
                                    @foreach ($chapters as $index => $chapter)
                                        @if ($chapter['title'] !== null)
                                            <li>
                                                <a href="#{{ $chapter['id'] }}" :aria-current="(chapter === {{ $index + 1 }}).toString()"
                                                    x-on:click="$el.closest('details').removeAttribute('open')">
                                                    <span class="sg-toc__num">{{ $index + 1 }}</span>
                                                    <span>{{ $chapter['title'] }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ol>
                            </nav>
                        </details>
                    @endif
                    <div class="sg-reader__group" role="group" aria-label="Ambiance de lecture">
                        @foreach (['jour' => 'Jour', 'crepuscule' => 'Crépuscule', 'nuit' => 'Nuit'] as $key => $label)
                            <button type="button" class="sg-amb sg-amb--{{ $key }}" aria-label="Ambiance {{ mb_strtolower($label) }}" title="{{ $label }}"
                                aria-pressed="{{ $key === 'jour' ? 'true' : 'false' }}" :aria-pressed="(ambiance === @js($key)).toString()" @click="setAmbiance(@js($key))">
                                <span class="sg-amb__dot" aria-hidden="true"></span>
                                <span class="sg-amb__label">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="sg-reader__group" role="group" aria-label="Taille du texte">
                        <button type="button" class="sg-size" aria-label="Réduire le texte" @click="resize(-1)" :disabled="size <= 16">A−</button>
                        <button type="button" class="sg-size sg-size--big" aria-label="Agrandir le texte" @click="resize(1)" :disabled="size >= 26">A+</button>
                    </div>
                </div>
            </div>
            <div class="sg-reader__progress" @if ($hasText) role="progressbar" aria-label="Progression de la lecture" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="Math.round(progress * 100)" @else aria-hidden="true" @endif>
                <span :style="'transform: scaleX(' + progress + ')'"></span>
            </div>
        </div>

        {{-- En-tête de l'œuvre --}}
        <header class="w-container sg-reader__head sg-tone" style="{{ \App\Support\SongeSky::style($genre) }}">
            <div class="sg-reader__cover">
                <span class="sg-book__cover sg-shape--{{ $genre ?? 'none' }}">
                    <img src="{{ $coverUrl }}" alt="{{ $document->title }} par {{ $authorName }}">
                </span>
                <span class="sg-book__medal" aria-hidden="true"><x-genre-emblem :genre="$genre" class="sg-book__emblem" /></span>
            </div>
            <div class="sg-reader__intro">
                @if ($genre)
                    <p class="sg-reader__kicker">{{ mb_strtoupper($document->genreLabel()) }}</p>
                @endif
                <h1 class="sg-reader__title">{{ $document->title }}</h1>
                <p class="sg-reader__byline">
                    par <a href="{{ route('public.writer', $writer) }}">{{ $authorName }}</a>
                    @if ($readingMinutes)
                        <span aria-hidden="true">·</span> environ {{ $readingMinutes }} min de lecture
                    @endif
                    @if ($titledChapters->count() >= 2)
                        <span aria-hidden="true">·</span> {{ \App\Support\Fr::count($titledChapters->count(), 'chapitre') }}
                    @endif
                </p>
                @if ($document->description)
                    <p class="sg-reader__desc">{{ $document->description }}</p>
                @endif
                <div class="sg-reader__actions">
                    @auth
                        <form method="POST" action="{{ $isBookmarked ? route('documents.unbookmark', $document) : route('documents.bookmark', $document) }}">
                            @csrf
                            @if ($isBookmarked)
                                @method('DELETE')
                            @endif
                            <button type="submit" class="sg-r-btn sg-r-btn--ghost">
                                {{ $isBookmarked ? 'Retirer le marque-page' : 'Ajouter un marque-page' }}
                            </button>
                        </form>
                    @else
                        <a href="{{ \App\Support\GoogleAuth::loginUrl() }}" class="sg-r-btn sg-r-btn--ghost">Se connecter pour marquer</a>
                    @endauth
                    @if ($canDownload)
                        <a href="{{ route('documents.download', $document) }}" class="sg-r-btn" rel="nofollow">Télécharger</a>
                    @elseif ($document->isDownloadable())
                        <span class="sg-reader__nodl">Lecture en ligne uniquement</span>
                    @endif
                    @if ($isOwner)
                        <a href="{{ route('documents.edit', $document) }}" class="sg-r-btn sg-r-btn--ghost">Modifier le texte</a>
                    @endif
                    <x-like-button :item="$document" />
                </div>
                <x-report-button :work="$document" />
                <x-share-buttons class="sg-share sg-share--reader" :title="'« '.$document->title.' » '.\App\Support\Fr::de($authorName).' sur '.$collectif['nom']" label="Partager ce récit" />
            </div>
        </header>

        {{-- Texte --}}
        <section class="sg-reader__body" aria-label="Lecture">
            @if ($hasText)
                <template x-if="resumeAt">
                    <div class="sg-resume" role="status">
                        <p>Ta lecture s'était arrêtée à <strong x-text="Math.round(resumeAt.progress * 100) + ' %'"></strong>.</p>
                        <div class="sg-resume__actions">
                            <button type="button" class="sg-r-btn" x-on:click="resume()">Reprendre la lecture</button>
                            <button type="button" class="sg-resume__dismiss" x-on:click="resumeAt = null">Recommencer au début</button>
                        </div>
                    </div>
                </template>

                <article class="sg-reader__text" x-ref="text">
                    @foreach ($chapters as $index => $chapter)
                        <section id="{{ $chapter['id'] }}" class="sg-chapter" data-chapter="{{ $index + 1 }}"
                            @if ($chapter['title'] !== null) aria-labelledby="{{ $chapter['id'] }}-titre" @endif>
                            @if ($chapter['title'] !== null)
                                <h2 id="{{ $chapter['id'] }}-titre" class="sg-chapter__title">{{ $chapter['title'] }}</h2>
                            @endif
                            @foreach ($chapter['blocks'] as $block)
                                @if ($block['type'] === 'sep')
                                    <div class="sg-sep" role="separator">
                                        <x-genre-emblem :genre="$genre" class="sg-sep__emblem" />
                                    </div>
                                @else
                                    <p @if (! $firstParagraphDone) class="sg-dropcap" @endif>{!! collect($block['lines'])->map(fn ($l) => e($l))->implode('<br>') !!}</p>
                                    @php($firstParagraphDone = true)
                                @endif
                            @endforeach
                            @if ($hasToc && isset($chapters[$index + 1]) && $chapters[$index + 1]['title'] !== null)
                                <p class="sg-chapter__next"><a href="#{{ $chapters[$index + 1]['id'] }}">{{ $chapters[$index + 1]['title'] }} →</a></p>
                            @endif
                        </section>
                    @endforeach
                </article>
            @elseif ($pdfUrl)
                <div class="w-container">
                    <p class="sg-reader__pdf-note">
                        Ce récit est publié en PDF : les ambiances, la taille du texte et les chapitres ne s'y appliquent pas.
                        @if ($isOwner)
                            <a href="{{ route('documents.edit', $document) }}">Ajoute la version texte</a> pour offrir la lecture complète du site.
                        @endif
                    </p>
                    <div class="sg-reader__pdf">
                        <iframe src="{{ $pdfUrl }}" title="{{ $document->title }}"></iframe>
                    </div>
                </div>
            @else
                <div class="w-container">
                    <div class="sg-reader__notice">
                        <h2>Lecture intégrée indisponible</h2>
                        <p>
                            @if ($document->file_type)
                                Ce fichier est au format {{ strtoupper($document->file_type) }} et ne peut pas être affiché dans la page.
                            @else
                                Le texte de cette œuvre n'est pas encore disponible.
                            @endif
                            @if ($isOwner)
                                Ajoute ton texte dans l'éditeur pour le rendre lisible sur le site.
                            @endif
                        </p>
                        @if ($isOwner)
                            <a href="{{ route('documents.edit', $document) }}" class="sg-r-btn">Ouvrir l'éditeur</a>
                        @elseif ($canDownload)
                            <a href="{{ route('documents.download', $document) }}" class="sg-r-btn" rel="nofollow">Télécharger le document</a>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        {{-- Navigation --}}
        <nav class="w-container sg-reader__nav" aria-label="Navigation entre les œuvres">
            <a href="{{ route('public.writer', $writer) }}" class="sg-reader__navlink">
                <span class="sg-reader__navhint"><span aria-hidden="true">←</span> Retour à la bibliothèque</span>
                <span class="sg-reader__navtitle">{{ $authorName }}</span>
            </a>
            @if ($nextDocument)
                <a href="{{ route('documents.read', $nextDocument) }}" class="sg-reader__navlink sg-reader__navlink--next">
                    <span class="sg-reader__navhint">Œuvre suivante <span aria-hidden="true">→</span></span>
                    <span class="sg-reader__navtitle">{{ $nextDocument->title }}</span>
                </a>
            @endif
        </nav>
    </div>
</x-world-layout>
