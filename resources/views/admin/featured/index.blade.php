@php
    $card = fn ($illustration) => [
        'id' => $illustration->id,
        'title' => $illustration->title,
        'author' => $illustration->artist?->user?->name ?? 'Artiste',
        'thumb' => $illustration->thumbnailUrl(),
    ];
    $pinnedData = $pinned->map($card)->values();
    $catalog = $illustrations->map($card)->values();
    $planned = $upcoming->mapWithKeys(fn ($s) => [$s->featured_for->toDateString() => ['artist' => $s->artist_id, 'writer' => $s->writer_id]]);
@endphp

<x-admin-layout title="Mises en avant" intro="Artiste et plume du jour de l’accueil, et œuvres épinglées en tête du fil et de la galerie.">
    <aside class="adm-card adc-priority" aria-labelledby="priority-title">
        <h2 id="priority-title" class="adm-card__title">Qui est mis en avant aujourd’hui ?</h2>
        <ol class="adc-priority__list">
            <li><strong>Planning</strong> : un profil planifié pour la date du jour passe avant tout le reste.</li>
            <li><strong>Choix manuel</strong> : sinon, le profil choisi ci-dessous, tant qu’il a au moins une œuvre publiée.</li>
            <li><strong>Tirage automatique</strong> : sinon (ou si le profil choisi n’a plus d’œuvre en ligne), un membre ayant publié est tiré chaque jour — jamais l’équipe, jamais la même personne deux jours de suite.</li>
        </ol>
        <p class="adm-help">Le membre mis en avant reçoit une notification le jour même.</p>
    </aside>

    {{-- Artiste / plume du jour --}}
    <section class="adm-card" aria-labelledby="daily-title">
        <h2 id="daily-title" class="adm-card__title">Artiste et plume du jour</h2>

        <form method="POST" action="{{ route('admin.featured.update') }}" class="adm-grid adm-grid--2 adc-daily">
            @csrf
            @method('PUT')

            @foreach ([
                ['prefix' => 'artiste', 'legend' => 'Artiste du jour', 'mode' => $artistMode, 'id' => $artistId, 'options' => $artists, 'empty' => 'Aucun illustrateur n’a encore d’œuvre publiée.'],
                ['prefix' => 'plume', 'legend' => 'Plume du jour', 'mode' => $writerMode, 'id' => $writerId, 'options' => $writers, 'empty' => 'Aucun écrivain n’a encore de texte publié.'],
            ] as $block)
                @php
                    $modeField = $block['prefix'].'_mode';
                    $idField = $block['prefix'].'_id';
                    $currentMode = old($modeField, $block['mode']);
                @endphp
                <fieldset class="adc-fieldset" x-data="{ mode: @js($currentMode) }">
                    <legend class="adm-label">{{ $block['legend'] }}</legend>
                    <label class="adm-check">
                        <input type="radio" name="{{ $modeField }}" value="auto" x-model="mode" @checked($currentMode === 'auto')>
                        <span>Tirage automatique quotidien</span>
                    </label>
                    <label class="adm-check">
                        <input type="radio" name="{{ $modeField }}" value="manuel" x-model="mode" @checked($currentMode === 'manuel') @disabled($block['options']->isEmpty())>
                        <span>Choix manuel</span>
                    </label>

                    <div class="adm-field adc-indent" x-show="mode === 'manuel'">
                        <label for="{{ $idField }}" class="adm-label">Membre choisi</label>
                        @if ($block['options']->isEmpty())
                            <p class="adm-help">{{ $block['empty'] }}</p>
                        @else
                            <select id="{{ $idField }}" name="{{ $idField }}" class="adm-select"
                                @error($idField) aria-invalid="true" aria-describedby="{{ $idField }}-error" @enderror>
                                <option value="">Choisir…</option>
                                @foreach ($block['options'] as $profile)
                                    <option value="{{ $profile->id }}" @selected((int) old($idField, $block['id']) === $profile->id)>{{ $profile->user?->name ?? 'Membre' }}</option>
                                @endforeach
                            </select>
                            <p class="adm-help">Seuls les membres ayant au moins une œuvre publiée sont proposés.</p>
                        @endif
                        @error($idField)
                            <p id="{{ $idField }}-error" class="adm-error">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>
            @endforeach

            <div class="adm-actions">
                <button type="submit" class="adm-btn">Enregistrer</button>
                <a href="{{ route('public.index') }}" class="adm-link" target="_blank" rel="noopener">Voir sur le site<span class="sr-only"> (nouvel onglet)</span> ↗</a>
            </div>
        </form>
    </section>

    {{-- Œuvres épinglées --}}
    <section class="adm-card" aria-labelledby="pinned-title">
        <h2 id="pinned-title" class="adm-card__title">Œuvres épinglées</h2>
        <p class="adm-card__intro">
            Elles apparaissent en premier, dans cet ordre, dans le fil de l’accueil et la galerie (tri « Récent »).
            Faites glisser une œuvre pour la déplacer, ou utilisez les boutons ↑ et ↓ au clavier.
        </p>

        <script>
            window.adcPinned = function (items, catalog, max) {
                return {
                    items: items, catalog: catalog, max: max, query: '', choice: '', dragIndex: null, message: '',
                    get results() {
                        var ids = this.items.map(function (i) { return i.id; });
                        var q = this.query.trim().toLowerCase();
                        return this.catalog.filter(function (c) {
                            return ids.indexOf(c.id) === -1 && (!q || (c.title + ' ' + c.author).toLowerCase().indexOf(q) !== -1);
                        }).slice(0, 50);
                    },
                    add: function () {
                        var id = parseInt(this.choice, 10);
                        var found = this.catalog.find(function (c) { return c.id === id; });
                        if (!found || this.items.length >= this.max) return;
                        this.items.push(found);
                        this.choice = '';
                        this.message = '« ' + found.title + ' » épinglée en position ' + this.items.length + '.';
                    },
                    remove: function (index) {
                        var removed = this.items.splice(index, 1)[0];
                        this.message = '« ' + removed.title + ' » retirée des œuvres épinglées.';
                    },
                    move: function (from, to, focusButton) {
                        if (to < 0 || to >= this.items.length || from === to) return;
                        var item = this.items.splice(from, 1)[0];
                        this.items.splice(to, 0, item);
                        this.message = '« ' + item.title + ' » déplacée en position ' + (to + 1) + ' sur ' + this.items.length + '.';
                        if (focusButton) {
                            var self = this;
                            this.$nextTick(function () {
                                var el = self.$root.querySelector('[data-move="' + focusButton + '-' + item.id + '"]');
                                if (el && !el.disabled) el.focus();
                            });
                        }
                    },
                    drop: function (index) {
                        if (this.dragIndex !== null) this.move(this.dragIndex, index);
                        this.dragIndex = null;
                    }
                };
            };
        </script>

        <form method="POST" action="{{ route('admin.featured.pinned') }}" x-data="adcPinned(@js($pinnedData), @js($catalog), {{ $maxPinned }})">
            @csrf
            @method('PUT')
            <input type="hidden" name="pinned_sent" value="1">

            <p class="sr-only" role="status" aria-live="polite" x-text="message"></p>

            <ol class="adc-pins" role="list" x-show="items.length">
                <template x-for="(item, index) in items" :key="item.id">
                    <li class="adc-pin" draggable="true"
                        :class="{ 'is-dragging': dragIndex === index }"
                        @dragstart="dragIndex = index; $event.dataTransfer.effectAllowed = 'move'"
                        @dragend="dragIndex = null"
                        @dragover.prevent
                        @drop.prevent="drop(index)">
                        <input type="hidden" name="pinned[]" :value="item.id">
                        <span class="adc-pin__handle" aria-hidden="true">⠿</span>
                        <span class="adc-pin__rank" x-text="index + 1"></span>
                        <img :src="item.thumb" alt="" class="adm-thumb">
                        <span class="adc-pin__text">
                            <strong x-text="item.title"></strong>
                            <span class="adm-muted" x-text="'par ' + item.author"></span>
                        </span>
                        <span class="adc-pin__actions">
                            <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" :data-move="'up-' + item.id"
                                :disabled="index === 0" :aria-label="'Monter « ' + item.title + ' »'" @click="move(index, index - 1, 'up')">↑</button>
                            <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" :data-move="'down-' + item.id"
                                :disabled="index === items.length - 1" :aria-label="'Descendre « ' + item.title + ' »'" @click="move(index, index + 1, 'down')">↓</button>
                            <button type="button" class="adm-btn adm-btn--danger adm-btn--sm" :aria-label="'Retirer « ' + item.title + ' »'" @click="remove(index)">Retirer</button>
                        </span>
                    </li>
                </template>
            </ol>
            <p class="adm-empty" x-show="!items.length">Aucune œuvre épinglée : le fil de l’accueil est tiré au sort chaque jour.</p>

            @error('pinned')
                <p class="adm-error" role="alert">{{ $message }}</p>
            @enderror

            <div class="adc-pin-add" x-show="items.length < max">
                <div class="adm-field">
                    <label for="pin-search" class="adm-label">Rechercher une illustration</label>
                    <input id="pin-search" type="search" class="adm-input" x-model="query" placeholder="Titre ou artiste" autocomplete="off">
                </div>
                <div class="adm-field">
                    <label for="pin-choice" class="adm-label">Illustration à épingler</label>
                    <select id="pin-choice" class="adm-select" x-model="choice">
                        <option value="">Choisir…</option>
                        <template x-for="result in results" :key="result.id">
                            <option :value="result.id" x-text="result.title + ' — ' + result.author"></option>
                        </template>
                    </select>
                </div>
                <button type="button" class="adm-btn adm-btn--ghost" @click="add()" :disabled="!choice">Épingler</button>
            </div>
            <p class="adm-help" x-show="items.length >= max">{{ $maxPinned }} œuvres au plus : retirez-en une pour en épingler une autre.</p>

            <div class="adm-actions">
                {{-- Désactivé tant qu'Alpine n'a pas pris la main : évite d'envoyer une liste vide par erreur. --}}
                <button type="submit" class="adm-btn" disabled x-bind:disabled="false">Enregistrer l’ordre</button>
            </div>
        </form>
    </section>

    {{-- Planning --}}
    <section class="adm-card" aria-labelledby="planning-title">
        <h2 id="planning-title" class="adm-card__title">Planning</h2>
        <p class="adm-card__intro">
            Réservez un jour précis (jusqu’à 60 jours à l’avance) pour un artiste ou une plume, par exemple pour une exposition.
            Ce jour-là, le planning passe avant le choix manuel et le tirage automatique.
        </p>

        <div class="adm-grid adm-grid--2">
            <form method="POST" action="{{ route('admin.featured.schedule') }}" x-data="{ planned: @js($planned) }">
                @csrf
                <div class="adm-field">
                    <label for="featured_date" class="adm-label">Jour</label>
                    <input id="featured_date" type="date" name="date" value="{{ old('date', today()->toDateString()) }}"
                        min="{{ today()->toDateString() }}" max="{{ today()->addDays(60)->toDateString() }}" required class="adm-input"
                        x-on:change="const p = planned[$event.target.value] || {}; $refs.artist.value = p.artist || ''; $refs.writer.value = p.writer || '';"
                        @error('date') aria-invalid="true" aria-describedby="featured_date-error" @enderror>
                    @error('date')
                        <p id="featured_date-error" class="adm-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="adm-field">
                    <label for="artist_id" class="adm-label">Artiste</label>
                    <select id="artist_id" name="artist_id" x-ref="artist" class="adm-select">
                        <option value="">Selon les réglages (auto / manuel)</option>
                        @foreach ($allArtists as $artist)
                            <option value="{{ $artist->id }}" @selected((int) old('artist_id', $planned[today()->toDateString()]['artist'] ?? 0) === $artist->id)>{{ $artist->user?->name ?? 'Artiste' }}</option>
                        @endforeach
                    </select>
                    @error('artist_id')
                        <p class="adm-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="adm-field">
                    <label for="writer_id" class="adm-label">Plume</label>
                    <select id="writer_id" name="writer_id" x-ref="writer" class="adm-select">
                        <option value="">Selon les réglages (auto / manuel)</option>
                        @foreach ($allWriters as $writer)
                            <option value="{{ $writer->id }}" @selected((int) old('writer_id', $planned[today()->toDateString()]['writer'] ?? 0) === $writer->id)>{{ $writer->user?->name ?? 'Plume' }}</option>
                        @endforeach
                    </select>
                    @error('writer_id')
                        <p class="adm-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="adm-actions">
                    <button type="submit" class="adm-btn">Planifier</button>
                </div>
            </form>

            <div>
                <h3 class="adm-label">Jours planifiés</h3>
                @if ($upcoming->isEmpty())
                    <p class="adm-muted">Aucun jour planifié : les réglages ci-dessus s’appliquent chaque jour.</p>
                @else
                    <ul class="adm-list" role="list">
                        @foreach ($upcoming as $selection)
                            @php($dayLabel = $selection->featured_for->isToday() ? 'Aujourd’hui' : ucfirst($selection->featured_for->locale('fr')->translatedFormat('l j F')))
                            <li class="adm-row adc-plan">
                                <div class="adc-plan__text">
                                    <strong>{{ $dayLabel }}</strong>
                                    <span class="adm-muted">Artiste : {{ $selection->artist?->user?->name ?? 'selon les réglages' }}</span>
                                    <span class="adm-muted">Plume : {{ $selection->writer?->user?->name ?? 'selon les réglages' }}</span>
                                </div>
                                <form method="POST" action="{{ route('admin.featured.destroy', $selection) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm" aria-label="Retirer la planification du {{ mb_strtolower($dayLabel) }}">Retirer</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
</x-admin-layout>
