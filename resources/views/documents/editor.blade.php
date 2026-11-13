@php
    $isNew = $document === null;
    $draftKey = 'aad-draft-'.($isNew ? 'nouveau-'.auth()->id() : $document->id);
    $content = old('content', $initialContent);
    $hasFile = ! $isNew && $document->hasFile();
    $fileIsPdf = $hasFile && $document->isPdf();
@endphp

<x-world-layout world="songe" :title="$isNew ? 'Écrire une œuvre' : 'Modifier « '.$document->title.' »'" description="Éditeur de texte des écrivains du collectif." robots-noindex>
    <div class="se" x-data="songeEditor(@js($draftKey), @js($content))">
        <div class="w-container se-head">
            <a href="{{ route('dashboard') }}" class="se-back"><span aria-hidden="true">←</span> Mon espace</a>
            <h1 class="songe-title se-title">{{ $isNew ? 'Écrire une œuvre' : $document->title }}</h1>
            @unless ($isNew)
                <p class="se-meta">
                    <strong>{{ $document->statusLabel() }}</strong>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('documents.read', $document) }}">{{ $document->isVisible() ? 'Voir la page de lecture ↗' : 'Prévisualiser ↗' }}</a>
                    <span aria-hidden="true">·</span>
                    {{ \App\Support\Fr::count($document->view_count, 'vue') }}
                    <span aria-hidden="true">·</span>
                    {{ \App\Support\Fr::count($document->download_count, 'téléchargement') }}
                    <span aria-hidden="true">·</span>
                    {{ \App\Support\Fr::count($document->likesTotal(), 'J’aime', 'J’aime') }}
                </p>
            @endunless

            @if (session('success'))
                <p class="se-flash se-flash--ok" role="status">{{ session('success') }}</p>
            @endif
            @if ($errors->any())
                <div class="se-flash se-flash--error" role="alert">
                    <p><strong>Impossible d'enregistrer :</strong></p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($fileIsPdf && blank($document->content))
                <p class="se-flash se-flash--info">
                    Ce texte est publié en PDF : sur la page de lecture, il s'affiche dans un cadre, sans les ambiances, la taille du texte ni les chapitres.
                    Colle ton texte ci-dessous pour offrir la lecture complète du site. Le PDF restera disponible au téléchargement.
                </p>
            @endif
        </div>

        <form method="POST" action="{{ $isNew ? route('documents.store') : route('documents.save', $document) }}" enctype="multipart/form-data"
            class="w-container se-layout" x-on:submit="clearDraft()">
            @csrf
            @unless ($isNew)
                @method('PUT')
            @endunless
            <input type="hidden" name="from_editor" value="1">

            {{-- Texte --}}
            <section class="se-writing songe-card" aria-labelledby="se-text-label">
                <div class="se-toolbar" role="toolbar" aria-label="Mise en forme">
                    <label id="se-text-label" for="se-content" class="se-label">Texte de l'œuvre</label>
                    <div class="se-toolbar__buttons">
                        <button type="button" class="se-tool" x-on:click="insertChapter()">+ Nouveau chapitre</button>
                        <button type="button" class="se-tool" x-on:click="insert('\n\n* * *\n\n')">Pause « * * * »</button>
                    </div>
                </div>

                <template x-if="restored">
                    <p class="se-draft" role="status">
                        Un brouillon non enregistré a été restauré.
                        <button type="button" x-on:click="discardDraft()">Revenir à la version enregistrée</button>
                    </p>
                </template>

                <textarea id="se-content" name="content" x-ref="content" x-model="text" x-on:input.debounce.800ms="saveDraft()"
                    class="se-textarea" rows="24" maxlength="{{ \App\Http\Controllers\DocumentController::CONTENT_MAX }}" spellcheck="true"
                    aria-describedby="se-help se-stats"
                    placeholder="# Chapitre 1&#10;&#10;Il était une fois…">{{ $content }}</textarea>

                <p id="se-stats" class="se-stats" aria-live="polite">
                    <span x-text="stats.words + (stats.words >= 2 ? ' mots' : ' mot')"></span>
                    <span aria-hidden="true">·</span>
                    <span x-text="stats.chapters ? stats.chapters + (stats.chapters >= 2 ? ' chapitres' : ' chapitre') : 'sans chapitre'"></span>
                    <span aria-hidden="true">·</span>
                    <span x-text="'≈ ' + Math.max(1, Math.ceil(stats.words / 230)) + ' min de lecture'"></span>
                </p>

                <details id="se-help" class="se-help">
                    <summary>Comment mettre en forme ?</summary>
                    <ul>
                        <li>Laisse une <strong>ligne vide</strong> entre deux paragraphes.</li>
                        <li>Commence une ligne par <code>#</code> pour ouvrir un <strong>chapitre</strong> : <code># Chapitre 1 — La lagune</code>. Une ligne seule « Chapitre 2 », « Partie II » ou « Prologue » fonctionne aussi.</li>
                        <li>Une ligne <code>* * *</code> marque une <strong>pause</strong> dans un chapitre (elle s'affiche avec l'emblème du genre).</li>
                        <li>Tu peux coller directement un texte depuis Word ou Google Docs.</li>
                    </ul>
                </details>
            </section>

            {{-- Informations --}}
            <aside class="se-side">
                <div class="songe-card se-panel">
                    <div class="se-field">
                        <label for="se-title" class="se-label">Titre</label>
                        <input id="se-title" name="title" type="text" required maxlength="255" value="{{ old('title', $document?->title) }}">
                    </div>

                    <fieldset class="se-field">
                        <legend class="se-label">Genre <span class="se-required">obligatoire</span></legend>
                        <div class="se-genres">
                            @foreach ($genres ?? \App\Models\Genre::options($document?->genre) as $value => $label)
                                <label class="se-genre">
                                    <input type="radio" name="genre" value="{{ $value }}" required @checked(old('genre', $document?->genre) === $value)>
                                    <span><x-genre-emblem :genre="$value" class="se-genre__emblem" /> {{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="se-field">
                        <label for="se-desc" class="se-label">Résumé <span class="se-optional">facultatif</span></label>
                        <textarea id="se-desc" name="description" rows="4" maxlength="2000">{{ old('description', $document?->description) }}</textarea>
                    </div>
                </div>

                <div class="songe-card se-panel">
                    <div class="se-field">
                        <label for="se-cover" class="se-label">Couverture {{ $isNew ? '' : '(remplacer)' }}</label>
                        @unless ($isNew)
                            <img src="{{ $document->coverThumbnailUrl() }}" alt="Couverture actuelle" class="se-cover">
                        @endunless
                        <input id="se-cover" name="cover_image" type="file" accept="image/*" @if ($isNew) required @endif>
                    </div>

                    <div class="se-field">
                        <label for="se-file" class="se-label">Fichier joint <span class="se-optional">facultatif</span></label>
                        @if ($hasFile)
                            <p class="se-hint">Fichier actuel : {{ strtoupper($document->file_type) }} ({{ number_format($document->file_size_bytes / 1024 / 1024, 1, ',', ' ') }} Mo)</p>
                        @endif
                        <input id="se-file" name="document" type="file" accept=".pdf,.txt,.doc,.docx">
                        <p class="se-hint">PDF, TXT, DOC ou DOCX. Le texte écrit ici reste prioritaire pour la lecture sur le site.</p>
                        @if ($hasFile)
                            <label class="se-check">
                                <input type="checkbox" name="remove_file" value="1">
                                <span>Retirer le fichier joint (seulement si le texte est rempli)</span>
                            </label>
                        @endif
                    </div>

                    @if (\App\Support\PlatformRules::authorChoosesDownload())
                    <div class="se-field">
                        <input type="hidden" name="allow_download" value="0">
                        <label class="se-check">
                            <input type="checkbox" name="allow_download" value="1" @checked(old('allow_download', $document?->allow_download ?? true))>
                            <span><strong>Autoriser le téléchargement</strong><br>Les lecteurs peuvent télécharger l'œuvre (fichier joint, ou texte en .txt).</span>
                        </label>
                    </div>
                    @endif
                </div>

                <div class="songe-card se-panel">
                    <x-publication-fields :model="$document" id-prefix="doc" />
                    <x-rights-checkbox what="ce texte" id-prefix="doc-rights" />
                </div>

                <button type="submit" class="songe-btn se-submit">{{ $isNew ? 'Enregistrer l’œuvre' : 'Enregistrer les modifications' }}</button>
            </aside>
        </form>
    </div>

    <x-slot name="head">
        <script>
            document.addEventListener('alpine:init', function () {
                window.Alpine.data('songeEditor', function (draftKey, initial) {
                    return {
                        text: initial,
                        saved: initial,
                        restored: false,
                        init() {
                            try {
                                var draft = localStorage.getItem(draftKey);
                                if (draft !== null && draft !== this.text && draft.trim() !== '') {
                                    this.text = draft;
                                    this.restored = true;
                                }
                            } catch (e) {}
                        },
                        get stats() {
                            var t = this.text.trim();
                            return {
                                words: t ? t.split(/\s+/u).length : 0,
                                chapters: (t.match(/^#{1,3}\s+\S/gmu) || []).length,
                            };
                        },
                        saveDraft() {
                            try {
                                if (this.text === this.saved) localStorage.removeItem(draftKey);
                                else localStorage.setItem(draftKey, this.text);
                            } catch (e) {}
                        },
                        discardDraft() {
                            this.text = this.saved;
                            this.restored = false;
                            try { localStorage.removeItem(draftKey); } catch (e) {}
                        },
                        clearDraft() {
                            try { localStorage.removeItem(draftKey); } catch (e) {}
                        },
                        insert(snippet) {
                            var el = this.$refs.content, start = el.selectionStart, end = el.selectionEnd;
                            this.text = this.text.slice(0, start) + snippet + this.text.slice(end);
                            this.$nextTick(function () {
                                el.focus();
                                el.selectionStart = el.selectionEnd = start + snippet.length;
                            });
                            this.saveDraft();
                        },
                        insertChapter() {
                            var n = this.stats.chapters + 1;
                            this.insert((this.text.trim() ? '\n\n' : '') + '# Chapitre ' + n + '\n\n');
                        },
                    };
                });
            });
        </script>
    </x-slot>
</x-world-layout>
