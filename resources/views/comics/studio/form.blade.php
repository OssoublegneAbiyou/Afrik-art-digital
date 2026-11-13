@php
    /** @var \App\Models\Comic|null $comic */
    use App\Models\Comic;
    use App\Models\Genre;
    use App\Support\ComicImages;

    $isNew = $comic === null;
    $typeLocked = ! $isNew && $comic->allChapters()->count() > 1;
    $type = old('type', $comic?->type ?? Comic::ONE_SHOT);
    $resume = old('resume', $comic?->resume ?? '');
    $tags = old('tags', $comic ? implode(', ', $comic->tags ?? []) : '');
    $field = fn (string $name) => $errors->has($name) ? 'aria-invalid=true aria-describedby='.$name.'-error' : '';
@endphp

<x-world-layout world="neo" :title="$isNew ? 'Nouveau récit dessiné' : 'Fiche · '.$comic->titre" description="Assistant de création d’un récit dessiné : la fiche." robots-noindex>
    <div class="nd cs">
        <div class="w-container nd-body">
            <p class="cs-back"><a href="{{ $isNew ? route('dashboard').'#mes-recits-dessines' : route('comics.studio.manage', $comic) }}" class="cs-link">← {{ $isNew ? 'Mon espace artiste' : 'Retour à « '.$comic->titre.' »' }}</a></p>
            <h1 class="nd-h2 font-bebas cs-h1">{{ $isNew ? 'Nouveau récit dessiné' : 'Fiche du récit' }}</h1>

            @include('comics.studio._steps', ['current' => 1, 'comic' => $comic, 'chapter' => $chapter])
            @include('comics.studio._flash')

            <form method="POST" action="{{ $isNew ? route('comics.studio.store') : route('comics.studio.update', $comic) }}" enctype="multipart/form-data"
                class="neo-card nd-panel cs-form" x-data="{ type: @js($type), resume: @js($resume) }">
                @csrf
                @unless ($isNew)
                    @method('PUT')
                    @if ($chapter)
                        <input type="hidden" name="chapitre" value="{{ $chapter->id }}">
                    @endif
                @endunless

                <div class="nd-panel__body nd-form">
                    @if ($typeLocked)
                        <p class="nd-hint">Type : <strong>{{ $comic->typeLabel() }}</strong> (la série a déjà plusieurs chapitres).</p>
                    @else
                        <fieldset class="nd-field">
                            <legend>Format du récit <span class="nd-required">obligatoire</span></legend>
                            <div class="nd-choices">
                                <label class="nd-choice">
                                    <input type="radio" name="type" value="{{ Comic::ONE_SHOT }}" x-model="type" @checked($type === Comic::ONE_SHOT) required>
                                    <span>One-shot</span>
                                </label>
                                <label class="nd-choice">
                                    <input type="radio" name="type" value="{{ Comic::SERIES }}" x-model="type" @checked($type === Comic::SERIES) required>
                                    <span>Série en chapitres</span>
                                </label>
                            </div>
                            <p class="nd-hint" x-show="type === '{{ Comic::ONE_SHOT }}'">Une histoire complète, publiée en une fois.</p>
                            <p class="nd-hint" x-show="type === '{{ Comic::SERIES }}'" x-cloak>Des chapitres publiés au fil du temps ; tes lecteurs peuvent suivre la série.</p>
                        </fieldset>
                    @endif

                    <div class="nd-field">
                        <label for="cs-titre">Titre <span class="nd-required">obligatoire</span></label>
                        <input id="cs-titre" name="titre" type="text" class="neo-input" required maxlength="120" value="{{ old('titre', $comic?->titre) }}" {!! $field('titre') !!}>
                        @error('titre')<p id="titre-error" class="nd-error">{{ $message }}</p>@enderror
                    </div>

                    @if ($isNew)
                        <div class="nd-field" x-show="type === '{{ Comic::SERIES }}'" x-cloak>
                            <label for="cs-chapitre-titre">Titre du chapitre 1 <span class="nd-hint">facultatif</span></label>
                            <input id="cs-chapitre-titre" name="chapitre_titre" type="text" class="neo-input" maxlength="120" value="{{ old('chapitre_titre') }}">
                        </div>
                    @endif

                    <div class="nd-field">
                        <label for="cs-resume">Résumé <span class="nd-hint">600 caractères au plus</span></label>
                        <textarea id="cs-resume" name="resume" rows="4" maxlength="600" class="neo-input nd-textarea" x-model="resume"
                            aria-describedby="cs-resume-count" {!! $errors->has('resume') ? 'aria-invalid=true' : '' !!}>{{ $resume }}</textarea>
                        <p id="cs-resume-count" class="nd-hint" aria-live="polite"><span x-text="600 - resume.length">{{ 600 - mb_strlen($resume) }}</span> caractères restants</p>
                        @error('resume')<p class="nd-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="nd-field">
                        <label for="cs-couverture">
                            Couverture <span class="nd-hint">obligatoire pour publier · format portrait 2:3 conseillé · JPG, PNG ou WEBP · {{ ComicImages::maxPageMb() }} Mo max</span>
                        </label>
                        @if ($comic?->coverUrl())
                            <img src="{{ $comic->coverUrl() }}" alt="Couverture actuelle" class="cs-cover-preview" width="160">
                        @endif
                        <input id="cs-couverture" name="couverture" type="file" accept="image/jpeg,image/png,image/webp" class="nd-file" {!! $field('couverture') !!}>
                        @error('couverture')<p id="couverture-error" class="nd-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="cs-grid-2">
                        <div class="nd-field">
                            <label for="cs-genre">Genre <span class="nd-hint">facultatif</span></label>
                            <select id="cs-genre" name="genre" class="neo-input nd-select">
                                <option value="">— Aucun —</option>
                                @foreach (Genre::options($comic?->genre) as $value => $label)
                                    <option value="{{ $value }}" @selected(old('genre', $comic?->genre) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('genre')<p class="nd-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="nd-field">
                            <label for="cs-tags">Mots-clés <span class="nd-hint">séparés par des virgules · {{ Comic::MAX_TAGS }} au plus</span></label>
                            <input id="cs-tags" name="tags" type="text" class="neo-input" maxlength="300" value="{{ $tags }}" placeholder="Abidjan, action, lycée">
                        </div>
                    </div>

                    <div class="cs-grid-2">
                        <fieldset class="nd-field">
                            <legend>Lecture par défaut</legend>
                            <div class="cs-radios">
                                @foreach (Comic::FORMATS as $value => $label)
                                    <label class="cs-radio"><input type="radio" name="format_lecture" value="{{ $value }}" @checked(old('format_lecture', $comic?->format_lecture ?? 'vertical') === $value) required> {{ $label }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <fieldset class="nd-field">
                            <legend>Sens de lecture</legend>
                            <div class="cs-radios">
                                @foreach (Comic::DIRECTIONS as $value => $label)
                                    <label class="cs-radio"><input type="radio" name="sens_lecture" value="{{ $value }}" @checked(old('sens_lecture', $comic?->sens_lecture ?? 'ltr') === $value) required> {{ $label }}</label>
                                @endforeach
                            </div>
                            <p class="nd-hint">Utile en lecture page par page.</p>
                        </fieldset>
                    </div>

                    <div class="nd-field" x-show="type === '{{ Comic::SERIES }}'" @if ($type !== Comic::SERIES) x-cloak @endif>
                        <label for="cs-statut">État de la série</label>
                        <select id="cs-statut" name="statut_serie" class="neo-input nd-select">
                            @foreach (Comic::SERIES_STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('statut_serie', $comic?->statut_serie ?? 'en_cours') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="cs-check" for="cs-sensible">
                        <input type="hidden" name="contenu_sensible" value="0">
                        <input id="cs-sensible" type="checkbox" name="contenu_sensible" value="1" @checked(old('contenu_sensible', $comic?->contenu_sensible))>
                        <span><strong>Contenu sensible</strong> (violence, horreur…) : un avertissement s’affiche avant la lecture.</span>
                    </label>

                    @if ($isNew)
                        <div class="rights">
                            <label class="rights__label" for="cs-rights">
                                <input id="cs-rights" type="checkbox" name="rights" value="1" required @checked(old('rights')) {!! $field('rights') !!}>
                                <span><strong>Je certifie être l’auteur de ces planches</strong> et avoir le droit de les publier : pas de copie, ni de personnages, logos ou visuels appartenant à d’autres.</span>
                            </label>
                            <p class="rights__note">
                                <a href="{{ route('pages.rules') }}" target="_blank" rel="noopener">Lire les règles de publication<span class="sr-only"> (s’ouvre dans un nouvel onglet)</span> <span aria-hidden="true">↗</span></a>
                            </p>
                            @error('rights')<p id="rights-error" class="nd-error">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div class="nd-row cs-actions">
                        <button type="submit" class="neo-btn neo-btn--a1">{{ $isNew || $chapter ? 'Enregistrer et passer aux pages →' : 'Enregistrer la fiche' }}</button>
                        @unless ($isNew)
                            <a href="{{ route('comics.studio.manage', $comic) }}" class="neo-btn neo-btn--ghost">Annuler</a>
                        @endunless
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-world-layout>
