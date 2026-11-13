@php
    use App\Http\Controllers\Admin\TaxonomyController;
    use App\Support\SongeSky;
    use App\Support\SvgSanitizer;

    $isNew = $genre === null;
    $colors = [
        'ciel_jour' => ['Ciel de jour', 'Fond du ciel en mode jour.', '#B79CF0'],
        'encre_jour' => ['Encre de jour', 'Texte et emblème sur le ciel de jour : assez contrasté.', '#1E1640'],
        'ciel_nuit' => ['Ciel de nuit', 'Fond du ciel en mode nuit (plutôt sombre).', '#1A0F3D'],
        'encre_nuit' => ['Encre de nuit', 'Texte sur le ciel de nuit (plutôt clair).', '#F3E6FF'],
        'lueur_nuit' => ['Lueur de nuit', 'Halo des emblèmes et des étoiles la nuit.', '#C9B8FF'],
    ];
    $values = collect($colors)->mapWithKeys(fn ($c, $field) => [$field => old($field, $genre?->{$field} ?? $c[2])])->all();
    $emblem = old('emblem', $genre?->emblem);
    $emblemParts = SvgSanitizer::parts($emblem);
    $error = fn (string $field) => $errors->first($field);
    $invalid = fn (string $field, string $id, ?string $help = null) => trim(
        ($errors->has($field) ? 'aria-invalid=true ' : '')
        .(($help || $errors->has($field)) ? 'aria-describedby="'.trim(($help ? $id.'-aide ' : '').($errors->has($field) ? $id.'-erreur' : '')).'"' : '')
    );
@endphp

<x-admin-layout :title="$isNew ? 'Nouveau genre' : 'Genre « '.$genre->nom.' »'" intro="Un genre est un ciel de la page Écrivains : il apparaît dans les filtres, les royaumes, les couvertures et la liseuse.">
    <x-slot:actions>
        <a href="{{ route('admin.taxonomy.index') }}" class="adm-btn adm-btn--ghost">← Types & genres</a>
        @if (! $isNew && $genre->actif)
            <a href="{{ route('public.writers') }}" class="adm-link" target="_blank" rel="noopener">Voir sur le site ↗<span class="sr-only"> (nouvel onglet)</span></a>
        @endif
    </x-slot:actions>

    <form method="POST" action="{{ $isNew ? route('admin.taxonomy.genres.store') : route('admin.taxonomy.genres.update', $genre) }}"
        x-data="genreForm(@js($values), @js(TaxonomyController::emblemSvg($emblemParts)), @js(route('admin.taxonomy.genres.preview')))">
        @csrf
        @unless ($isNew)
            @method('PUT')
        @endunless

        <div class="adm-grid adm-grid--2">
            {{-- Texte --}}
            <section class="adm-card" aria-labelledby="genre-infos">
                <h2 id="genre-infos" class="adm-card__title">Nom et textes</h2>

                <div class="adm-field">
                    <label class="adm-label" for="genre-nom">Nom</label>
                    <input id="genre-nom" name="nom" type="text" class="adm-input" maxlength="60" required
                        value="{{ old('nom', $genre?->nom) }}" {!! $invalid('nom', 'genre-nom', 'aide') !!}>
                    <p id="genre-nom-aide" class="adm-help">
                        @if ($isNew)
                            L’adresse (slug) est calculée à partir du nom à la création, puis ne change plus.
                        @else
                            Adresse figée : <code>{{ $genre->slug }}</code>
                        @endif
                    </p>
                    @if ($error('nom'))
                        <p id="genre-nom-erreur" class="adm-error">{{ $error('nom') }}</p>
                    @endif
                </div>

                <div class="adm-field" x-data="{ len: {{ mb_strlen((string) old('phrase_accroche', $genre?->phrase_accroche)) }} }">
                    <label class="adm-label" for="genre-phrase">Phrase d’accroche <span class="adm-muted">(facultatif)</span></label>
                    <textarea id="genre-phrase" name="phrase_accroche" rows="2" maxlength="200" class="adm-textarea" x-model="tagline"
                        x-on:input="len = $event.target.value.length" {!! $invalid('phrase_accroche', 'genre-phrase', 'aide') !!}>{{ old('phrase_accroche', $genre?->phrase_accroche) }}</textarea>
                    <p id="genre-phrase-aide" class="adm-help">Affichée dans le ciel de la page Écrivains quand ce genre est choisi. <span class="adm-counter" x-text="len + ' / 200'"></span></p>
                    @if ($error('phrase_accroche'))
                        <p id="genre-phrase-erreur" class="adm-error">{{ $error('phrase_accroche') }}</p>
                    @endif
                </div>

                <div class="adm-field">
                    <label class="adm-label" for="genre-description">Description du royaume <span class="adm-muted">(facultatif)</span></label>
                    <input id="genre-description" name="description" type="text" class="adm-input" maxlength="160"
                        value="{{ old('description', $genre?->description) }}" {!! $invalid('description', 'genre-description', 'aide') !!}>
                    <p id="genre-description-aide" class="adm-help">Une ligne sous le nom, dans « Les royaumes du songe ».</p>
                    @if ($error('description'))
                        <p id="genre-description-erreur" class="adm-error">{{ $error('description') }}</p>
                    @endif
                </div>

                <div class="adm-field">
                    <label class="adm-label" for="genre-ordre">Ordre</label>
                    <input id="genre-ordre" name="ordre" type="number" min="0" max="9999" inputmode="numeric" class="adm-input"
                        value="{{ old('ordre', $genre?->ordre) }}" {!! $invalid('ordre', 'genre-ordre', 'aide') !!}>
                    <p id="genre-ordre-aide" class="adm-help">Position dans les listes (1 = en premier). Vide : à la fin. Vous pouvez aussi utiliser les flèches de la liste.</p>
                    @if ($error('ordre'))
                        <p id="genre-ordre-erreur" class="adm-error">{{ $error('ordre') }}</p>
                    @endif
                </div>

                <input type="hidden" name="actif" value="0">
                <label class="adm-check" for="genre-actif">
                    <input id="genre-actif" type="checkbox" name="actif" value="1" @checked(old('actif', $genre?->actif ?? true))>
                    <span><strong>Actif</strong><br><span class="adm-muted">Proposé aux écrivains et affiché comme ciel. Désactivé : les récits existants gardent leur genre.</span></span>
                </label>
            </section>

            {{-- Aperçu --}}
            <section class="adm-card" aria-labelledby="genre-apercu">
                <h2 id="genre-apercu" class="adm-card__title">Aperçu</h2>
                <div class="adm-grid adm-grid--2" style="margin-top: 12px;" aria-hidden="true">
                    <div :style="'background:' + sky('ciel_jour') + ';color:' + c.encre_jour" style="border-radius: 16px; padding: 18px; min-height: 180px;">
                        <p style="font-size: .75rem; letter-spacing: .12em; text-transform: uppercase;">Jour</p>
                        <span x-html="svg" style="display: block; width: 72px; height: 72px; margin: 8px 0;"></span>
                        <p style="font-size: .9rem;" x-text="tagline"></p>
                    </div>
                    <div :style="'background:' + sky('ciel_nuit') + ';color:' + c.encre_nuit" style="border-radius: 16px; padding: 18px; min-height: 180px;">
                        <p style="font-size: .75rem; letter-spacing: .12em; text-transform: uppercase;">Nuit</p>
                        <span x-html="svg" :style="'color:' + c.lueur_nuit + ';filter: drop-shadow(0 0 8px ' + c.lueur_nuit + ')'" style="display: block; width: 72px; height: 72px; margin: 8px 0;"></span>
                        <p style="font-size: .9rem;" x-text="tagline"></p>
                    </div>
                </div>
                <p class="adm-help">L’aperçu suit vos saisies ; l’emblème est montré tel qu’il sera affiché, une fois nettoyé.</p>
            </section>
        </div>

        {{-- Couleurs --}}
        <section class="adm-card" aria-labelledby="genre-couleurs">
            <h2 id="genre-couleurs" class="adm-card__title">Couleurs</h2>
            <p class="adm-card__intro adm-muted">Format #RRGGBB. Pour les ciels, un dégradé est calculé à partir de la couleur choisie ; les dégradés des genres d’origine sont conservés tant que vous ne changez pas la couleur.</p>
            <div class="adm-grid adm-grid--3">
                @foreach ($colors as $field => [$label, $help])
                    <div class="adm-field">
                        <label class="adm-label" for="genre-{{ $field }}">{{ $label }}</label>
                        <div class="adm-row" style="flex-wrap: nowrap;">
                            <input type="color" aria-label="{{ $label }} : sélecteur de couleur" style="width: 48px; height: 44px; padding: 2px; flex: none; border-radius: 10px;"
                                :value="first(c.{{ $field }})" x-on:input="c.{{ $field }} = $event.target.value.toUpperCase()"
                                value="{{ SongeSky::firstColor((string) $values[$field]) }}">
                            <input id="genre-{{ $field }}" name="{{ $field }}" type="text" class="adm-input" maxlength="160" required spellcheck="false" autocomplete="off"
                                x-model="c.{{ $field }}" value="{{ $values[$field] }}" {!! $invalid($field, 'genre-'.$field, 'aide') !!}>
                        </div>
                        <p id="genre-{{ $field }}-aide" class="adm-help">{{ $help }}</p>
                        @if ($error($field))
                            <p id="genre-{{ $field }}-erreur" class="adm-error">{{ $error($field) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Emblème --}}
        <section class="adm-card" aria-labelledby="genre-embleme">
            <h2 id="genre-embleme" class="adm-card__title">Emblème</h2>
            <div class="adm-grid adm-grid--2">
                <div class="adm-field">
                    <label class="adm-label" for="genre-emblem">Code SVG <span class="adm-muted">(facultatif)</span></label>
                    <textarea id="genre-emblem" name="emblem" rows="10" class="adm-textarea" spellcheck="false" maxlength="20000" style="font-family: ui-monospace, monospace; font-size: .85rem;"
                        x-on:input.debounce.400ms="preview($event.target.value)" {!! $invalid('emblem', 'genre-emblem', 'aide') !!}>{{ $emblem }}</textarea>
                    <p id="genre-emblem-aide" class="adm-help">
                        Dessin au trait, idéalement dans un carré <code>viewBox="0 0 120 120"</code> ; la couleur suit celle du ciel.
                        Seules les formes simples sont gardées (path, circle, ellipse, rect, line, polyline, polygon, g) : scripts, styles, liens et images sont retirés.
                        Vide : un livre ouvert.
                    </p>
                    @if ($error('emblem'))
                        <p id="genre-emblem-erreur" class="adm-error">{{ $error('emblem') }}</p>
                    @endif
                </div>
                <div>
                    <p class="adm-label" id="emblem-preview-label">Aperçu nettoyé</p>
                    <div class="adm-preview" role="img" aria-labelledby="emblem-preview-label" style="display: grid; place-items: center; min-height: 140px; color: var(--ink);">
                        <span x-html="svg" style="display: block; width: 96px; height: 96px;">{!! TaxonomyController::emblemSvg($emblemParts) !!}</span>
                    </div>
                    <p class="adm-help" x-show="! valid" x-cloak role="status">Aucune forme utilisable : le livre ouvert sera affiché.</p>
                </div>
            </div>
        </section>

        <div class="adm-actions" style="margin-top: 18px;">
            <button type="submit" class="adm-btn">{{ $isNew ? 'Ajouter le genre' : 'Enregistrer' }}</button>
            <a href="{{ route('admin.taxonomy.index') }}" class="adm-btn adm-btn--ghost">Annuler</a>
        </div>
    </form>

    @unless ($isNew)
        <section class="adm-card" aria-labelledby="genre-suppression" style="margin-top: 18px;">
            <h2 id="genre-suppression" class="adm-card__title">Supprimer</h2>
            @if ($usage > 0)
                <p class="adm-muted">Ce genre est utilisé par {{ \App\Support\Fr::count($usage, 'récit') }} : il ne peut pas être supprimé. Décochez « Actif » pour ne plus le proposer.</p>
            @else
                <p class="adm-muted">Aucun récit n’utilise ce genre.</p>
                <form method="POST" action="{{ route('admin.taxonomy.genres.destroy', $genre) }}" class="adm-actions"
                    onsubmit="return confirm({{ \Illuminate\Support\Js::from('Supprimer le genre « '.$genre->nom.' » ?') }});">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="adm-btn adm-btn--danger">Supprimer le genre</button>
                </form>
            @endif
        </section>
    @endunless

    <script>
        document.addEventListener('alpine:init', function () {
            window.Alpine.data('genreForm', function (colors, svg, previewUrl) {
                var HEX = /^#[0-9A-Fa-f]{6}$/;
                function mix(hex, other, ratio) {
                    var a = [1, 3, 5].map(function (i) { return parseInt(hex.substr(i, 2), 16); });
                    var b = [1, 3, 5].map(function (i) { return parseInt(other.substr(i, 2), 16); });
                    return '#' + a.map(function (v, i) { return Math.round(v + (b[i] - v) * ratio).toString(16).padStart(2, '0'); }).join('');
                }
                return {
                    c: colors,
                    svg: svg,
                    valid: true,
                    tagline: document.getElementById('genre-phrase') ? document.getElementById('genre-phrase').value : '',
                    first(value) {
                        var match = String(value || '').match(/#[0-9A-Fa-f]{6}/);
                        return match ? match[0] : '#000000';
                    },
                    sky(field) {
                        var value = String(this.c[field] || '').trim();
                        if (HEX.test(value)) {
                            return 'linear-gradient(160deg,' + mix(value, '#FFFFFF', .45) + ',' + value + ' 55%,' + mix(value, '#000000', .35) + ')';
                        }
                        return /^linear-gradient\(\s*\d{1,3}deg(\s*,\s*#[0-9A-Fa-f]{6}(\s+\d{1,3}%)?){2,5}\s*\)$/.test(value) ? value : 'transparent';
                    },
                    preview(value) {
                        var token = document.querySelector('input[name=_token]').value;
                        fetch(previewUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            body: JSON.stringify({ emblem: value }),
                        })
                            .then(function (response) { return response.ok ? response.json() : null; })
                            .then((data) => { if (data) { this.svg = data.svg; this.valid = data.valid || value.trim() === ''; } })
                            .catch(function () {});
                    },
                };
            });
        });
    </script>
</x-admin-layout>
