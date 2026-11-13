@php
    /** @var array $field  ['key', 'name', 'definition', 'value', 'image_url'] — voir SettingsController::edit */
    $definition = $field['definition'];
    $type = $definition['type'];
    $name = $field['name'];
    $id = 'reglage-'.$name;
    $max = $definition['max'] ?? null;
    $error = $errors->first($name);
    $help = $definition['help'] ?? match ($type) {
        'url' => 'Adresse complète (https://…) ou identifiant (@nom). Vide : le lien n’apparaît pas sur le site.',
        default => null,
    };
    $describedBy = trim(($help ? $id.'-aide ' : '').($error ? $id.'-erreur' : ''));

    $current = match ($type) {
        'json' => implode("\n", (array) $field['value']),
        'boolean' => (bool) $field['value'],
        default => $field['value'],
    };
    $value = old($name, $current);
    $required = ! empty($definition['required']);

    // Décor : le champ alimente l'aperçu en direct (x-data du formulaire).
    $model = ($live ?? false) ? 'x-model="v.'.$name.'"' : '';
    $fieldLink = $definition['link'] ?? null;
    $showFieldLink = $fieldLink && $fieldLink !== ($tabLink ?? null) && \Illuminate\Support\Facades\Route::has($fieldLink);
@endphp

<div class="adm-field adms-field"
    @if ($max && in_array($type, ['text', 'textarea', 'richtext'], true))
        x-data="{ len: {{ mb_strlen((string) $value) }}, max: {{ $max }} }"
    @endif
>
    @if ($type === 'boolean')
        <input type="hidden" name="{{ $name }}" value="0">
        <label class="adm-check" for="{{ $id }}">
            <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked((bool) $value)
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if ($error) aria-invalid="true" @endif>
            <span><strong>{{ $definition['label'] }}</strong></span>
        </label>
    @else
        <label class="adm-label" for="{{ $id }}">
            {{ $definition['label'] }}
            @if ($required)
                <span class="adms-required">(obligatoire)</span>
            @endif
            @if ($max && in_array($type, ['text', 'textarea', 'richtext'], true))
                <span class="adm-counter" aria-hidden="true" x-text="len + ' / ' + max" :data-over="len > max ? '' : null">{{ mb_strlen((string) $value) }} / {{ $max }}</span>
            @endif
        </label>

        @php
            $common = 'id="'.$id.'" name="'.$name.'"'
                .($describedBy ? ' aria-describedby="'.$describedBy.'"' : '')
                .($error ? ' aria-invalid="true"' : '')
                .($required ? ' required' : '');
            $counter = $max ? ' @input="len = Array.from($event.target.value).length"' : '';
        @endphp

        @switch ($type)
            @case ('textarea')
                <textarea {!! $common !!} {!! $counter !!} {!! $model !!} class="adm-textarea" rows="{{ ($max ?? 0) > 400 ? 6 : 4 }}">{{ $value }}</textarea>
                @break

            @case ('richtext')
                <textarea {!! $common !!} {!! $counter !!} class="adm-textarea adms-richtext" rows="12" spellcheck="true">{{ $value }}</textarea>
                <p class="adm-help">
                    HTML simple autorisé : <code>&lt;p&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code>,
                    <code>&lt;a href="https://…"&gt;</code>, <code>&lt;ul&gt;</code>/<code>&lt;ol&gt;</code> avec <code>&lt;li&gt;</code>,
                    <code>&lt;h2&gt;</code>, <code>&lt;h3&gt;</code>, <code>&lt;blockquote&gt;</code>.
                    Sans balise, une ligne vide sépare deux paragraphes. Le reste (scripts, styles, images…) est retiré à l’enregistrement.
                    <code>{nom}</code> est remplacé par le nom du collectif.
                </p>
                @break

            @case ('json')
                <textarea {!! $common !!} {!! $model !!} class="adm-textarea adms-lines" rows="{{ max(3, count((array) $field['value']) + 1) }}" spellcheck="false">{{ $value }}</textarea>
                @break

            @case ('select')
                <select {!! $common !!} class="adm-select">
                    @foreach ($definition['options'] ?? [] as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @break

            @case ('integer')
                <input type="number" {!! $common !!} value="{{ $value }}" class="adm-input adms-input--short" inputmode="numeric" step="1"
                    @isset($definition['min']) min="{{ $definition['min'] }}" @endisset
                    @isset($definition['max_value']) max="{{ $definition['max_value'] }}" @endisset>
                @break

            @case ('image')
                <div class="adms-image" x-data="{ preview: null }">
                    @if ($field['image_url'])
                        <img src="{{ $field['image_url'] }}" alt="Image actuelle : {{ $definition['label'] }}" class="adm-preview" x-show="! preview">
                    @else
                        <p class="adm-muted" x-show="! preview">Aucune image pour l’instant.</p>
                    @endif
                    <img :src="preview" alt="Aperçu de la nouvelle image" class="adm-preview" x-show="preview" x-cloak>
                    <div class="adm-row adms-image__actions">
                        <span class="adms-file">
                            <input type="file" {!! $common !!} accept="image/jpeg,image/png,image/webp" class="adms-file__input"
                                @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                        </span>
                        @if ($field['image_url'])
                            <button type="submit" form="retirer-{{ $name }}" class="adm-btn adm-btn--danger adm-btn--sm">Retirer</button>
                        @endif
                    </div>
                    <p class="adm-help">{{ $field['image_url'] ? 'Choisis un fichier pour remplacer l’image actuelle, puis enregistre.' : 'Choisis un fichier, puis enregistre.' }} JPG, PNG ou WEBP, {{ \App\Http\Controllers\Admin\SettingsController::IMAGE_MAX_KB / 1024 }} Mo au plus.</p>
                </div>
                @break

            @default
                @php
                    $inputType = match ($type) { 'email' => 'email', 'phone' => 'tel', default => 'text' };
                    $extra = match ($type) {
                        'email' => 'autocomplete="email" inputmode="email"',
                        'phone' => 'autocomplete="tel" inputmode="tel"',
                        'url' => 'inputmode="url" autocomplete="url" spellcheck="false"',
                        default => '',
                    };
                @endphp
                <input type="{{ $inputType }}" {!! $common !!} {!! $counter !!} {!! $model !!} {!! $extra !!} value="{{ $value }}" class="adm-input">
        @endswitch
    @endif

    @if ($help)
        <p id="{{ $id }}-aide" class="adm-help">
            {{ $help }}
            @if ($showFieldLink)
                <a href="{{ route($fieldLink) }}" target="_blank" rel="noopener" class="adm-link">Voir sur le site ↗<span class="sr-only"> (nouvel onglet)</span></a>
            @endif
        </p>
    @elseif ($showFieldLink)
        <p class="adm-help"><a href="{{ route($fieldLink) }}" target="_blank" rel="noopener" class="adm-link">Voir sur le site ↗<span class="sr-only"> (nouvel onglet)</span></a></p>
    @endif

    @if ($error)
        <p id="{{ $id }}-erreur" class="adm-error">{{ $error }}</p>
    @endif
</div>
