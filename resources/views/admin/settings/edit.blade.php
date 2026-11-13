@php
    $current = $tabs[$tab];
    $tabLink = $current['link'] ?? null;
    $siteUrl = $tabLink ? route($tabLink).(isset($current['anchor']) ? '#'.$current['anchor'] : '') : null;
    $hasImages = collect($fields)->contains(fn ($field) => $field['definition']['type'] === 'image');
    $imageFields = collect($fields)->filter(fn ($field) => $field['definition']['type'] === 'image' && $field['image_url']);
    $groupLabels = config('collectif.groups', []);
    $live = $tab === 'decor';

    // Aperçu du décor : valeurs initiales (après une erreur, celles saisies).
    $liveValues = $live ? collect($fields)->mapWithKeys(fn ($field) => [
        $field['name'] => (string) old($field['name'], is_array($field['value']) ? implode("\n", $field['value']) : $field['value']),
    ])->all() : [];
@endphp

<x-admin-layout title="Réglages du collectif" intro="Identité, textes, contacts et règles du site. Chaque onglet s’enregistre séparément.">
    <nav class="adm-tabs" aria-label="Sections des réglages">
        @foreach ($tabs as $slug => $info)
            <a href="{{ route('admin.settings.edit', ['onglet' => $slug]) }}" class="adm-tab" @if ($slug === $tab) aria-current="page" @endif>{{ $info['label'] }}</a>
        @endforeach
    </nav>

    <section class="adm-card" aria-labelledby="reglages-titre">
        <div class="adm-row adms-head">
            <h2 id="reglages-titre" class="adm-card__title">{{ $current['label'] }}</h2>
            @if ($siteUrl)
                <a href="{{ $siteUrl }}" target="_blank" rel="noopener" class="adm-link adms-head__link">Voir sur le site ↗<span class="sr-only"> (nouvel onglet)</span></a>
            @endif
        </div>

        @if ($tab === 'apparence')
            <p class="adm-card__intro">Couleurs principales des espaces visiteur, écrivain et illustrateur (tableaux de bord). Visible dès l’enregistrement.</p>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="adm-stack adms-form" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="onglet" value="apparence">

                @foreach ($dashboardThemes as $type => $theme)
                    @php
                        $background = old("themes.$type.background", $theme['background']);
                    @endphp
                    <fieldset class="adms-theme">
                        <legend class="adms-theme__title">Tableau de bord {{ $theme['label'] }}</legend>
                        <div class="adms-theme__swatch" aria-hidden="true" style="background: {{ $background }};"></div>

                        <div class="adm-grid adm-grid--2">
                            @foreach (['background' => 'Fond (couleur ou dégradé CSS)', 'panel' => 'Panneaux', 'text' => 'Texte', 'accent' => 'Accent'] as $part => $partLabel)
                                @php
                                    $fieldName = "themes[$type][$part]";
                                    $fieldId = "theme-$type-$part";
                                    $fieldError = $errors->first("themes.$type.$part");
                                @endphp
                                <div class="adm-field">
                                    <label class="adm-label" for="{{ $fieldId }}">{{ $partLabel }}</label>
                                    @if ($part === 'background')
                                        <input id="{{ $fieldId }}" name="{{ $fieldName }}" value="{{ $background }}" class="adm-input" spellcheck="false"
                                            @if ($fieldError) aria-invalid="true" aria-describedby="{{ $fieldId }}-erreur" @endif>
                                    @else
                                        <input type="color" id="{{ $fieldId }}" name="{{ $fieldName }}" value="{{ old("themes.$type.$part", $theme[$part]) }}" class="adm-input adms-color"
                                            @if ($fieldError) aria-invalid="true" aria-describedby="{{ $fieldId }}-erreur" @endif>
                                    @endif
                                    @if ($fieldError)
                                        <p id="{{ $fieldId }}-erreur" class="adm-error">{{ $fieldError }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <div class="adm-actions">
                    <button type="submit" class="adm-btn">Enregistrer</button>
                </div>
            </form>
        @else
            @if ($tab === 'anim')
                @include('animations.studio.admin.ffmpeg-warning')
                @if (\App\Support\Ffmpeg::available())
                    <p class="adm-card__intro">Conversion vidéo opérationnelle ({{ \App\Support\Ffmpeg::version() ?? 'ffmpeg' }}).</p>
                @endif
            @endif
            @if ($tab === 'connexion')
                {{-- État de la configuration Google (.env) : jamais la valeur du secret. --}}
                <p class="adm-card__intro">« Continuer avec Google » crée uniquement des comptes visiteurs. Les comptes illustrateur et écrivain restent attribués par l’équipe.</p>
                <ul class="adm-list" aria-label="Configuration de Google">
                    <li>ID client :
                        @if (\App\Support\GoogleAuth::hasClientId())
                            <span class="adm-tag adm-tag--ok">configuré</span>
                        @else
                            <span class="adm-tag adm-tag--off">manquant</span>
                        @endif
                    </li>
                    <li>Secret :
                        @if (\App\Support\GoogleAuth::hasClientSecret())
                            <span class="adm-tag adm-tag--ok">configuré</span>
                        @else
                            <span class="adm-tag adm-tag--off">manquant</span>
                        @endif
                    </li>
                    <li>Adresse de retour à déclarer chez Google : <code>{{ route('auth.google.callback') }}</code></li>
                </ul>
                @unless (\App\Support\GoogleAuth::configured())
                    <p class="adm-help">Tant que l’ID client ou le secret manque (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET dans le fichier .env, puis <code>php artisan config:clear</code>), aucun bouton Google n’est affiché.</p>
                @endunless
            @endif
            @if ($tab === 'regles')
                <p class="adm-card__intro">Limites appliquées aux envois des membres et au fonctionnement du site.</p>
            @elseif ($tab === 'decor')
                <p class="adm-card__intro">Textes des enseignes lumineuses (expo, concours…). Purement décoratifs : ils ne sont pas lus par les lecteurs d’écran. 40 caractères au plus par phrase.</p>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}" class="adms-form" novalidate
                @if ($hasImages) enctype="multipart/form-data" @endif
                @if ($live) x-data="{ v: @js($liveValues), lines(text) { return String(text || '').split(/\r?\n/).map(l => l.trim()).filter(l => l !== '') } }" @endif>
                @csrf
                @method('PUT')
                <input type="hidden" name="onglet" value="{{ $tab }}">

                @if ($live)
                    @include('admin.settings._decor-preview')
                @endif

                @php($lastGroup = null)
                @foreach ($fields as $field)
                    @if (count($current['groups']) > 1 && $field['definition']['group'] !== $lastGroup)
                        <h3 class="adms-group">{{ $groupLabels[$field['definition']['group']] ?? $field['definition']['group'] }}</h3>
                        @php($lastGroup = $field['definition']['group'])
                    @endif
                    @include('admin.settings._field', ['field' => $field, 'tabLink' => $tabLink, 'live' => $live])
                @endforeach

                <div class="adm-actions">
                    <button type="submit" class="adm-btn">Enregistrer</button>
                </div>
            </form>

            {{-- Formulaires des boutons « Retirer » (un formulaire ne peut pas en contenir un autre). --}}
            @foreach ($imageFields as $field)
                <form id="retirer-{{ $field['name'] }}" method="POST" action="{{ route('admin.settings.update') }}" hidden>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="onglet" value="{{ $tab }}">
                    <input type="hidden" name="retirer" value="{{ $field['name'] }}">
                </form>
            @endforeach
        @endif
    </section>
</x-admin-layout>
