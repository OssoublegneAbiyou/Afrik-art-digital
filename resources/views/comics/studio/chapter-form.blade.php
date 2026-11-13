@php
    /** @var \App\Models\Comic $comic */
    $proposed = rtrim(rtrim(number_format($nextNumber, 1, ',', ''), '0'), ',');
@endphp

<x-world-layout world="neo" :title="'Nouveau chapitre · '.$comic->titre" description="Ajouter un chapitre à une série." robots-noindex>
    <div class="nd cs">
        <div class="w-container nd-body">
            <p class="cs-back"><a href="{{ route('comics.studio.manage', $comic) }}" class="cs-link">← Gérer la série</a></p>
            <h1 class="nd-h2 font-bebas cs-h1">{{ $comic->titre }} <span class="cs-h1__sub">· Nouveau chapitre</span></h1>

            @include('comics.studio._flash')

            <form method="POST" action="{{ route('comics.studio.chapters.store', $comic) }}" class="neo-card nd-panel cs-form">
                @csrf
                <div class="nd-panel__body nd-form">
                    <div class="cs-grid-2">
                        <div class="nd-field">
                            <label for="cs-numero">Numéro <span class="nd-hint">proposé : {{ $proposed }} · décimale possible pour un bonus (2,5)</span></label>
                            <input id="cs-numero" name="numero" type="text" inputmode="decimal" class="neo-input" required value="{{ old('numero', $proposed) }}"
                                @error('numero') aria-invalid="true" aria-describedby="numero-error" @enderror>
                            @error('numero')<p id="numero-error" class="nd-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="nd-field">
                            <label for="cs-titre">Titre du chapitre <span class="nd-hint">facultatif</span></label>
                            <input id="cs-titre" name="titre" type="text" class="neo-input" maxlength="120" value="{{ old('titre') }}">
                            @error('titre')<p class="nd-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="nd-field">
                        <label for="cs-note">Mot de l’auteur <span class="nd-hint">affiché à la fin du chapitre · 500 caractères au plus</span></label>
                        <textarea id="cs-note" name="note_auteur" rows="3" maxlength="500" class="neo-input nd-textarea">{{ old('note_auteur') }}</textarea>
                        @error('note_auteur')<p class="nd-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="nd-row cs-actions">
                        <button type="submit" class="neo-btn neo-btn--a1">Créer et passer aux pages →</button>
                        <a href="{{ route('comics.studio.manage', $comic) }}" class="neo-btn neo-btn--ghost">Annuler</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-world-layout>
