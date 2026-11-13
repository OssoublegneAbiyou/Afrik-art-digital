@php
    /** @var \App\Models\Animation|null $animation */
    use App\Models\Animation;
    use App\Models\Genre;
    use App\Support\AnimationMedia;
    use App\Support\Ffmpeg;

    $isNew = $animation === null;
    $mode = old('mode', $animation?->mode ?? Animation::LOOP);
    $description = old('description', $animation?->description ?? '');
    $tags = old('tags', $animation ? implode(', ', $animation->tags ?? []) : '');
    $field = fn (string $name) => $errors->has($name) ? 'aria-invalid=true aria-describedby='.$name.'-error' : '';
    $blocked = $isNew && ! $ffmpegAvailable;
@endphp

<x-world-layout world="neo" :title="$isNew ? 'Nouvelle animation' : 'Modifier · '.$animation->titre" description="Envoi d’une animation courte." robots-noindex>
    <div class="nd cs as">
        <div class="w-container nd-body">
            <p class="cs-back"><a href="{{ $isNew ? route('dashboard').'#mes-animations' : route('animations.studio.manage', $animation) }}" class="cs-link">← {{ $isNew ? 'Mon espace artiste' : 'Retour à « '.$animation->titre.' »' }}</a></p>
            <h1 class="nd-h2 font-bebas cs-h1">{{ $isNew ? 'Nouvelle animation' : 'Modifier l’animation' }}</h1>

            @include('comics.studio._flash')

            @unless ($ffmpegAvailable)
                <div class="nd-flash nd-flash--warn as-unavailable" role="status">
                    <p><strong>{{ Ffmpeg::UNAVAILABLE_MESSAGE }}</strong></p>
                    <p>{{ $isNew ? 'L’envoi de nouvelles animations est suspendu.' : 'Tu peux modifier le texte, mais pas envoyer une nouvelle vidéo ni changer le mode.' }} Tes animations déjà converties restent lisibles. Réessaie plus tard.</p>
                </div>
            @endunless

            @if ($blocked)
                <p class="nd-row cs-actions"><a href="{{ route('animations.studio.index') }}" class="neo-btn neo-btn--ghost">Mes animations</a></p>
            @else
                <form method="POST" action="{{ $isNew ? route('animations.studio.store') : route('animations.studio.update', $animation) }}" enctype="multipart/form-data"
                    class="neo-card nd-panel cs-form" x-data="{ mode: @js($mode), description: @js($description), sending: false }" x-on:submit="sending = true">
                    @csrf
                    @unless ($isNew)
                        @method('PUT')
                    @endunless

                    <div class="nd-panel__body nd-form">
                        <div class="nd-field">
                            <label for="as-fichier">
                                {{ $isNew ? 'Vidéo' : 'Remplacer la vidéo' }}
                                @if ($isNew)<span class="nd-required">obligatoire</span>@else<span class="nd-hint">facultatif</span>@endif
                            </label>
                            <p class="nd-hint" id="as-fichier-help">
                                {{ AnimationMedia::formatsLabel() }} · {{ AnimationMedia::maxFileMb() }} Mo et {{ AnimationMedia::durationLabel(AnimationMedia::maxDuration()) }} au plus.
                                Les GIF sont convertis en vidéo, bien plus légère. Stockage : {{ $storage['usedLabel'] }} utilisés sur {{ $storage['limitLabel'] }}.
                            </p>
                            <input id="as-fichier" name="fichier" type="file" class="nd-file" accept="{{ AnimationMedia::acceptAttribute() }}"
                                @if ($isNew) required @endif @disabled(! $ffmpegAvailable)
                                aria-describedby="as-fichier-help{{ $errors->has('fichier') ? ' fichier-error' : '' }}" @error('fichier') aria-invalid="true" @enderror>
                            @error('fichier')<p id="fichier-error" class="nd-error">{{ $message }}</p>@enderror
                            @unless ($isNew)
                                <p class="nd-hint">Vidéo actuelle : {{ $animation->source_nom ?: 'sans nom' }}@if ($animation->durationLabel()) · {{ $animation->durationLabel() }}@endif.</p>
                            @endunless
                        </div>

                        <fieldset class="nd-field">
                            <legend>Usage <span class="nd-required">obligatoire</span></legend>
                            <div class="cs-radios">
                                <label class="cs-radio">
                                    <input type="radio" name="mode" value="{{ Animation::LOOP }}" x-model="mode" @checked($mode === Animation::LOOP) required @disabled(! $isNew && ! $ffmpegAvailable && $mode !== Animation::LOOP)>
                                    <span><strong>Boucle</strong> : muette, se lit toute seule en boucle (comme un GIF). Le son est retiré.</span>
                                </label>
                                <label class="cs-radio">
                                    <input type="radio" name="mode" value="{{ Animation::CLIP }}" x-model="mode" @checked($mode === Animation::CLIP) required @disabled(! $isNew && ! $ffmpegAvailable && $mode !== Animation::CLIP)>
                                    <span><strong>Clip</strong> : avec son, se lance au clic, avec les contrôles de lecture.</span>
                                </label>
                            </div>
                            @unless ($isNew)
                                <p class="nd-hint">Changer d’usage relance la conversion de la vidéo.</p>
                            @endunless
                            @error('mode')<p class="nd-error">{{ $message }}</p>@enderror
                        </fieldset>

                        <div class="nd-field">
                            <label for="as-titre">Titre <span class="nd-required">obligatoire</span></label>
                            <input id="as-titre" name="titre" type="text" class="neo-input" required maxlength="120" value="{{ old('titre', $animation?->titre) }}" {!! $field('titre') !!}>
                            @error('titre')<p id="titre-error" class="nd-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="nd-field">
                            <label for="as-description">Description <span class="nd-hint">1 000 caractères au plus</span></label>
                            <textarea id="as-description" name="description" rows="4" maxlength="1000" class="neo-input nd-textarea" x-model="description"
                                aria-describedby="as-description-count" {!! $errors->has('description') ? 'aria-invalid=true' : '' !!}>{{ $description }}</textarea>
                            <p id="as-description-count" class="nd-hint" aria-live="polite"><span x-text="1000 - description.length">{{ 1000 - mb_strlen($description) }}</span> caractères restants</p>
                            @error('description')<p class="nd-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="cs-grid-2">
                            <div class="nd-field">
                                <label for="as-genre">Genre <span class="nd-hint">facultatif</span></label>
                                <select id="as-genre" name="genre" class="neo-input nd-select">
                                    <option value="">— Aucun —</option>
                                    @foreach (Genre::options($animation?->genre) as $value => $label)
                                        <option value="{{ $value }}" @selected(old('genre', $animation?->genre) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('genre')<p class="nd-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="nd-field">
                                <label for="as-tags">Mots-clés <span class="nd-hint">séparés par des virgules</span></label>
                                <input id="as-tags" name="tags" type="text" class="neo-input" maxlength="300" value="{{ $tags }}" placeholder="boucle, Abidjan, personnage">
                            </div>
                        </div>

                        <label class="cs-check" for="as-sensible">
                            <input type="hidden" name="contenu_sensible" value="0">
                            <input id="as-sensible" type="checkbox" name="contenu_sensible" value="1" @checked(old('contenu_sensible', $animation?->contenu_sensible))>
                            <span><strong>Contenu sensible</strong> (violence, horreur…) : un avertissement s’affiche avant la lecture.</span>
                        </label>

                        @if ($isNew)
                            <div class="rights">
                                <label class="rights__label" for="as-rights">
                                    <input id="as-rights" type="checkbox" name="rights" value="1" required @checked(old('rights')) {!! $field('rights') !!}>
                                    <span><strong>Je certifie être l’auteur de cette animation</strong> et avoir le droit de la publier (images et son) : pas de copie, ni de personnages, logos, musiques ou visuels appartenant à d’autres.</span>
                                </label>
                                <p class="rights__note">
                                    <a href="{{ route('pages.rules') }}" target="_blank" rel="noopener">Lire les règles de publication<span class="sr-only"> (s’ouvre dans un nouvel onglet)</span> <span aria-hidden="true">↗</span></a>
                                </p>
                                @error('rights')<p id="rights-error" class="nd-error">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        <div class="nd-row cs-actions">
                            <button type="submit" class="neo-btn neo-btn--a1" x-bind:disabled="sending" x-bind:aria-busy="sending ? 'true' : 'false'">
                                <span x-show="! sending">{{ $isNew ? 'Envoyer la vidéo' : 'Enregistrer' }}</span>
                                <span x-show="sending" x-cloak>Envoi en cours…</span>
                            </button>
                            @unless ($isNew)
                                <a href="{{ route('animations.studio.manage', $animation) }}" class="neo-btn neo-btn--ghost">Annuler</a>
                            @endunless
                        </div>
                        @if ($isNew)
                            <p class="nd-hint">Après l’envoi, la vidéo est convertie en arrière-plan ; tu choisiras ensuite le poster et la publication.</p>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-world-layout>
