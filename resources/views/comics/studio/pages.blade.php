@php
    /** @var \App\Models\Comic $comic */
    /** @var \App\Models\ComicChapter $chapter */
    use App\Support\ComicImages;

    $oneShot = $comic->isOneShot();
    // Pages animées (§14) : courtes vidéos acceptées si les animations sont actives et ffmpeg présent.
    $videoExtensions = \App\Support\AnimationMedia::comicVideoExtensions();
    $uploadExtensions = [...ComicImages::extensions(), ...$videoExtensions];
    $config = [
        'state' => $state,
        'urls' => [
            'store' => route('comics.studio.pages.store', [$comic, $chapter]),
            'status' => route('comics.studio.pages.status', [$comic, $chapter]),
            'reorder' => route('comics.studio.pages.reorder', [$comic, $chapter]),
            'sort' => route('comics.studio.pages.sort', [$comic, $chapter]),
        ],
        'extensions' => $uploadExtensions,
        'maxBytes' => ComicImages::maxPageBytes(),
        'maxMb' => ComicImages::maxPageMb(),
        'formats' => ComicImages::formatsLabel().($videoExtensions ? ' ou vidéo '.\App\Support\AnimationMedia::formatsLabel() : ''),
    ];
@endphp

<x-world-layout world="neo" :title="($oneShot ? 'Planches · ' : 'Chapitre '.$chapter->numberLabel().' · ').$comic->titre" description="Assistant de création d’un récit dessiné : les planches." robots-noindex>
    <div class="nd cs">
        <div class="w-container nd-body">
            <p class="cs-back"><a href="{{ route('comics.studio.manage', $comic) }}" class="cs-link">← {{ $oneShot ? 'Retour à « '.$comic->titre.' »' : 'Gérer la série' }}</a></p>
            <h1 class="nd-h2 font-bebas cs-h1">
                {{ $comic->titre }}@unless ($oneShot) <span class="cs-h1__sub">· Chapitre {{ $chapter->numberLabel() }}@if ($chapter->titre) — {{ $chapter->titre }}@endif</span>@endunless
            </h1>

            @include('comics.studio._steps', ['current' => 2, 'comic' => $comic, 'chapter' => $chapter])
            @include('comics.studio._flash')

            {{-- Informations du chapitre (numéro, titre, mot de l'auteur) --}}
            <details class="cs-details" @if ($errors->hasAny(['numero', 'titre', 'note_auteur'])) open @endif>
                <summary>{{ $oneShot ? 'Mot de l’auteur (fin de lecture)' : 'Numéro, titre et mot de l’auteur' }}</summary>
                <form method="POST" action="{{ route('comics.studio.chapters.update', [$comic, $chapter]) }}" class="nd-form cs-details__body">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="retour" value="pages">
                    @unless ($oneShot)
                        <div class="cs-grid-2">
                            <div class="nd-field">
                                <label for="cs-numero">Numéro <span class="nd-hint">ex. 3 ou 2,5 (bonus)</span></label>
                                <input id="cs-numero" name="numero" type="text" inputmode="decimal" class="neo-input" required value="{{ old('numero', $chapter->numberLabel()) }}">
                                @error('numero')<p class="nd-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="nd-field">
                                <label for="cs-chapitre-titre">Titre du chapitre <span class="nd-hint">facultatif</span></label>
                                <input id="cs-chapitre-titre" name="titre" type="text" class="neo-input" maxlength="120" value="{{ old('titre', $chapter->titre) }}">
                            </div>
                        </div>
                    @endunless
                    <div class="nd-field">
                        <label for="cs-note">Mot de l’auteur <span class="nd-hint">affiché à la fin de la lecture · 500 caractères au plus</span></label>
                        <textarea id="cs-note" name="note_auteur" rows="3" maxlength="500" class="neo-input nd-textarea">{{ old('note_auteur', $chapter->note_auteur) }}</textarea>
                        @error('note_auteur')<p class="nd-error">{{ $message }}</p>@enderror
                    </div>
                    <div><button type="submit" class="neo-btn neo-btn--a2">Enregistrer</button></div>
                </form>
            </details>

            <section class="cs-uploader" aria-labelledby="cs-pages-title" x-data="comicPages(@js($config))" x-init="init()">
                <div class="nd-section__head">
                    <h2 id="cs-pages-title" class="nd-h2 font-bebas">Planches</h2>
                    <p class="nd-count" aria-live="polite" x-text="summary()">{{ \App\Support\Fr::count($state['count'], 'page') }} · {{ number_format($state['bytes'] / 1048576, 1, ',', ' ') }} Mo</p>
                </div>

                <div class="cs-drop" :class="{ 'is-over': over }"
                    x-on:dragover.prevent="if (isFileDrag($event)) over = true" x-on:dragleave.prevent="over = false" x-on:drop.prevent="dropFiles($event)">
                    <p class="cs-drop__title">Glisse tes planches ici</p>
                    <p class="nd-hint">
                        Plusieurs fichiers à la fois · {{ ComicImages::formatsLabel() }} · {{ ComicImages::maxPageMb() }} Mo max chacun ·
                        {{ ComicImages::maxPagesPerChapter() }} pages au plus{{ $oneShot ? '' : ' par chapitre' }}.
                        Rangées automatiquement par nom de fichier (01, 02… 10).
                    </p>
                    @if ($videoExtensions)
                        <p class="nd-hint">
                            Case animée : une courte vidéo ({{ \App\Support\AnimationMedia::formatsLabel() }}, {{ \App\Support\AnimationMedia::COMIC_VIDEO_MAX_SECONDS }} s au plus)
                            devient une page en boucle muette, lue quand elle est à l’écran.
                        </p>
                    @endif
                    <label for="cs-files" class="neo-btn neo-btn--a1 cs-drop__btn">Choisir des fichiers</label>
                    <input id="cs-files" type="file" multiple class="sr-only" accept="{{ collect($uploadExtensions)->map(fn ($e) => '.'.$e)->implode(',') }}"
                        x-ref="files" x-on:change="pickFiles($event)">
                </div>

                <div class="cs-progress" x-show="uploading.total > 0" x-cloak role="status">
                    <p>Envoi <span x-text="uploading.done"></span> sur <span x-text="uploading.total"></span><span x-show="uploading.current"> : <span x-text="uploading.current"></span></span>…</p>
                    <span class="nd-meter" aria-hidden="true"><span :style="'width:' + Math.round(uploading.done / uploading.total * 100) + '%'"></span></span>
                </div>

                <div class="nd-flash nd-flash--error" x-show="errors.length" x-cloak role="alert">
                    <ul><template x-for="(error, i) in errors" :key="i"><li x-text="error"></li></template></ul>
                    <button type="button" class="cs-link" x-on:click="errors = []">Masquer</button>
                </div>

                <div class="nd-row cs-tools" x-show="pages.length > 1" x-cloak>
                    <button type="button" class="neo-btn neo-btn--ghost" x-on:click="sortByName()">Trier par nom de fichier</button>
                    <p class="nd-hint">Réordonne en glissant les vignettes, ou avec les flèches ← → de chaque page.</p>
                </div>

                <p class="sr-only" aria-live="polite" x-text="announce"></p>

                <ol class="cs-pages" role="list" x-show="pages.length" x-cloak>
                    <template x-for="(page, index) in pages" :key="page.id">
                        <li class="cs-page" :class="{ 'is-dragging': dragId === page.id, 'is-error': page.statut === 'erreur', 'is-double': page.double_page }"
                            draggable="true" x-on:dragstart="dragStart($event, page)" x-on:dragend="dragId = null"
                            x-on:dragover.prevent x-on:drop.prevent="dropOn(index, $event)">
                            <div class="cs-page__media">
                                <template x-if="page.statut === 'pret' && page.miniature">
                                    <img :src="page.miniature" :alt="'Page ' + (index + 1)" loading="lazy" width="150">
                                </template>
                                <template x-if="page.statut === 'en_attente'">
                                    <span class="cs-page__wait"><span class="cs-spinner" aria-hidden="true"></span>Préparation…</span>
                                </template>
                                <template x-if="page.statut === 'erreur'">
                                    <span class="cs-page__fail" x-text="page.erreur || 'Erreur'"></span>
                                </template>
                                <span class="cs-page__n" x-text="index + 1"></span>
                                <span class="cs-page__badge" x-show="page.tranches > 0">Webtoon</span>
                                <span class="cs-page__badge" x-show="page.anime">Animée</span>
                            </div>
                            <div class="cs-page__body">
                                <p class="cs-page__name" x-text="page.nom"></p>
                                <div class="cs-page__move">
                                    <button type="button" class="cs-icon" :data-move="page.id + '-left'" x-on:click="move(index, -1)" :disabled="index === 0"
                                        :aria-label="'Déplacer la page ' + (index + 1) + ' vers la gauche'">←</button>
                                    <button type="button" class="cs-icon" :data-move="page.id + '-right'" x-on:click="move(index, 1)" :disabled="index === pages.length - 1"
                                        :aria-label="'Déplacer la page ' + (index + 1) + ' vers la droite'">→</button>
                                </div>
                                <label class="cs-check cs-check--sm">
                                    <input type="checkbox" :checked="page.double_page" x-on:change="save(page, { double_page: $event.target.checked ? 1 : 0 })">
                                    <span>Double page</span>
                                </label>
                                <label class="sr-only" :for="'alt-' + page.id" x-text="'Description de la page ' + (index + 1)"></label>
                                <input type="text" class="neo-input cs-page__alt" :id="'alt-' + page.id" maxlength="255" placeholder="Description (facultatif)"
                                    :value="page.alt || ''" x-on:change="save(page, { alt: $event.target.value })">
                                <div class="cs-page__actions">
                                    <label class="cs-mini" :for="'replace-' + page.id">Remplacer</label>
                                    <input type="file" class="sr-only" :id="'replace-' + page.id" accept="{{ collect($uploadExtensions)->map(fn ($e) => '.'.$e)->implode(',') }}"
                                        x-on:change="replace(page, $event)">
                                    <button type="button" class="cs-mini cs-mini--danger" x-on:click="remove(page, index)"
                                        :aria-label="'Supprimer la page ' + (index + 1)">Supprimer</button>
                                </div>
                            </div>
                        </li>
                    </template>
                </ol>

                <p class="nd-empty" x-show="!pages.length" @if ($state['count']) x-cloak @endif>Aucune planche pour le moment. Tes envois sont conservés : tu peux quitter la page et revenir plus tard.</p>

                <div class="nd-row cs-actions cs-actions--between">
                    <a href="{{ route('comics.studio.edit', [$comic, 'chapitre' => $chapter->id]) }}" class="neo-btn neo-btn--ghost">← La fiche</a>
                    <a href="{{ route('comics.studio.chapters.publication', [$comic, $chapter]) }}" class="neo-btn neo-btn--a1">Continuer vers la publication →</a>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', function () {
            window.Alpine.data('comicPages', function (config) {
                var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                function request(url, method, body) {
                    var options = { method: method, headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
                    if (body instanceof FormData) {
                        options.body = body;
                    } else if (body) {
                        options.headers['Content-Type'] = 'application/json';
                        options.body = JSON.stringify(body);
                    }
                    return fetch(url, options).then(function (response) {
                        return response.json().catch(function () { return {}; }).then(function (data) {
                            if (!response.ok) {
                                var message = (data.errors && Object.values(data.errors)[0] && Object.values(data.errors)[0][0]) || data.message
                                    || (response.status === 413 ? 'Fichier trop lourd pour le serveur.' : 'L’envoi a échoué (erreur ' + response.status + ').');
                                throw new Error(message);
                            }
                            return data;
                        });
                    });
                }

                function natural(a, b) {
                    return a.name.localeCompare(b.name, 'fr', { numeric: true, sensitivity: 'base' });
                }

                return {
                    pages: [],
                    count: 0,
                    bytes: 0,
                    max: config.state.max,
                    errors: [],
                    announce: '',
                    over: false,
                    dragId: null,
                    timer: null,
                    uploading: { total: 0, done: 0, current: '' },

                    init: function () {
                        this.apply(config.state);
                    },

                    apply: function (state) {
                        var current = {};
                        this.pages.forEach(function (page) { current[page.id] = page; });
                        // Fusion : les champs en cours de saisie ne sont pas écrasés par le « polling ».
                        this.pages = state.pages.map(function (page) {
                            var existing = current[page.id];
                            if (!existing) return page;
                            Object.assign(existing, page);
                            return existing;
                        });
                        this.count = state.count;
                        this.bytes = state.bytes;
                        this.schedulePoll(state.pending > 0);
                    },

                    schedulePoll: function (needed) {
                        var self = this;
                        clearTimeout(this.timer);
                        if (!needed) return;
                        this.timer = setTimeout(function () {
                            request(config.urls.status, 'GET').then(function (data) { self.apply(data.state); })
                                .catch(function () { self.schedulePoll(true); });
                        }, 2500);
                    },

                    summary: function () {
                        var mo = (this.bytes / 1048576).toFixed(1).replace('.', ',');
                        return this.count + (this.count > 1 ? ' pages' : ' page') + ' · ' + mo + ' Mo';
                    },

                    isFileDrag: function (event) {
                        return event.dataTransfer && Array.prototype.indexOf.call(event.dataTransfer.types, 'Files') !== -1;
                    },

                    pickFiles: function (event) {
                        this.upload(Array.prototype.slice.call(event.target.files));
                        event.target.value = '';
                    },

                    dropFiles: function (event) {
                        this.over = false;
                        if (!this.isFileDrag(event)) return;
                        this.upload(Array.prototype.slice.call(event.dataTransfer.files));
                    },

                    check: function (file) {
                        var ext = (file.name.split('.').pop() || '').toLowerCase();
                        if (config.extensions.indexOf(ext) === -1) return file.name + ' : format non accepté (' + config.formats + ').';
                        if (file.size > config.maxBytes) return file.name + ' : plus de ' + config.maxMb + ' Mo.';
                        return null;
                    },

                    upload: function (files) {
                        var self = this;
                        this.errors = [];
                        // Tri naturel : « 2.jpg » avant « 10.jpg ».
                        files.sort(natural);
                        var valid = files.filter(function (file) {
                            var problem = self.check(file);
                            if (problem) self.errors.push(problem);
                            return !problem;
                        });
                        if (!valid.length) return;

                        this.uploading = { total: valid.length, done: 0, current: '' };
                        var chain = Promise.resolve();
                        valid.forEach(function (file) {
                            chain = chain.then(function () {
                                self.uploading.current = file.name;
                                var data = new FormData();
                                data.append('page', file);
                                return request(config.urls.store, 'POST', data)
                                    .then(function (response) { self.apply(response.state); })
                                    .catch(function (error) { self.errors.push(error.message.indexOf(file.name) === 0 ? error.message : file.name + ' : ' + error.message); })
                                    .then(function () { self.uploading.done++; });
                            });
                        });
                        chain.then(function () {
                            var sent = self.uploading.total;
                            self.announce = sent + (sent > 1 ? ' fichiers traités.' : ' fichier traité.');
                            self.uploading = { total: 0, done: 0, current: '' };
                        });
                    },

                    saveOrder: function () {
                        var self = this;
                        return request(config.urls.reorder, 'PUT', { pages: this.pages.map(function (page) { return page.id; }) })
                            .then(function (data) { self.apply(data.state); })
                            .catch(function (error) { self.errors = [error.message]; });
                    },

                    move: function (index, delta) {
                        var target = index + delta;
                        if (target < 0 || target >= this.pages.length) return;
                        var page = this.pages.splice(index, 1)[0];
                        this.pages.splice(target, 0, page);
                        this.announce = 'Page déplacée en position ' + (target + 1) + ' sur ' + this.pages.length + '.';
                        this.saveOrder();
                        var key = page.id + (delta < 0 ? '-left' : '-right');
                        this.$nextTick(function () {
                            var button = document.querySelector('[data-move="' + key + '"]');
                            if (button && button.disabled) button = document.querySelector('[data-move="' + page.id + (delta < 0 ? '-right' : '-left') + '"]');
                            if (button) button.focus();
                        });
                    },

                    dragStart: function (event, page) {
                        this.dragId = page.id;
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', String(page.id));
                    },

                    dropOn: function (index, event) {
                        if (this.isFileDrag(event)) { this.dropFiles(event); return; }
                        var id = this.dragId;
                        this.dragId = null;
                        var from = this.pages.findIndex(function (page) { return page.id === id; });
                        if (from === -1 || from === index) return;
                        var page = this.pages.splice(from, 1)[0];
                        this.pages.splice(index, 0, page);
                        this.announce = 'Page déplacée en position ' + (index + 1) + ' sur ' + this.pages.length + '.';
                        this.saveOrder();
                    },

                    sortByName: function () {
                        var self = this;
                        request(config.urls.sort, 'POST').then(function (data) {
                            self.apply(data.state);
                            self.announce = 'Pages triées par nom de fichier.';
                        }).catch(function (error) { self.errors = [error.message]; });
                    },

                    save: function (page, values) {
                        var self = this;
                        request(page.urls.update, 'PATCH', values)
                            .then(function (data) { Object.assign(page, data.page); self.announce = 'Enregistré.'; })
                            .catch(function (error) { self.errors = [error.message]; });
                    },

                    replace: function (page, event) {
                        var self = this;
                        var file = event.target.files[0];
                        event.target.value = '';
                        if (!file) return;
                        var problem = this.check(file);
                        if (problem) { this.errors = [problem]; return; }
                        var data = new FormData();
                        data.append('page', file);
                        page.statut = 'en_attente';
                        request(page.urls.replace, 'POST', data)
                            .then(function (response) { self.apply(response.state); self.announce = 'Planche remplacée.'; })
                            .catch(function (error) { self.errors = [error.message]; self.schedulePoll(true); });
                    },

                    remove: function (page, index) {
                        var self = this;
                        if (!window.confirm('Supprimer la page ' + (index + 1) + ' ?')) return;
                        request(page.urls.destroy, 'DELETE')
                            .then(function (data) { self.apply(data.state); self.announce = 'Page ' + (index + 1) + ' supprimée.'; })
                            .catch(function (error) { self.errors = [error.message]; });
                    },
                };
            });
        });
    </script>
</x-world-layout>
