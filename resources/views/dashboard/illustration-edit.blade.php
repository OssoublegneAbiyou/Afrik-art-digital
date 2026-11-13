<x-world-layout world="neo" :title="'Modifier « '.$illustration->title.' »'" description="Modifier une illustration." robots-noindex>
    <div class="w-container nd-body">
        <a href="{{ route('dashboard') }}" class="neo-btn neo-btn--ghost">← Mon espace</a>

        <h1 class="nd-title font-bebas">Modifier <span class="nd-title__name">{{ $illustration->title }}</span></h1>

        @if (session('success'))
            <p class="nd-flash nd-flash--ok" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="nd-flash nd-flash--error" role="alert">
                <p><strong>Impossible d'enregistrer :</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="ne-layout">
            <aside class="ne-preview">
                <img src="{{ $illustration->thumbnailUrl() }}" alt="{{ $illustration->title }}">
                <p class="nd-status" @if ($illustration->isVisible()) style="background: var(--a2); color: var(--on-a2)" @endif>{{ $illustration->statusLabel() }}</p>
                @if ($illustration->isRejected() && $illustration->moderation_note)
                    <p class="nd-hint">Motif : {{ $illustration->moderation_note }}</p>
                @endif
                <p class="nd-work__stats">
                    <span>👁 {{ \App\Support\Fr::count($illustration->view_count, 'vue') }}</span>
                    <span>♥ {{ \App\Support\Fr::count($illustration->likes_count, 'J’aime', 'J’aime') }}</span>
                    <span>★ {{ \App\Support\Fr::count($illustration->favorited_by_count, 'favori') }}</span>
                </p>
                <p class="mt-4">
                    <a href="{{ route('public.illustration', $illustration) }}" class="neo-btn neo-btn--ghost">
                        {{ $illustration->isVisible() ? 'Voir la page publique ↗' : 'Prévisualiser ↗' }}
                    </a>
                </p>
            </aside>

            <form method="POST" action="{{ route('illustrations.update', $illustration) }}" enctype="multipart/form-data" class="neo-card nd-panel">
                @csrf
                @method('PATCH')
                <input type="hidden" name="full" value="1">

                <div class="nd-panel__body nd-form">
                    <div class="nd-field">
                        <label for="edit-title">Titre</label>
                        <input id="edit-title" name="title" type="text" class="neo-input" value="{{ old('title', $illustration->title) }}" maxlength="255" required>
                    </div>

                    <fieldset class="nd-field">
                        <legend>Type</legend>
                        <div class="nd-choices">
                            @foreach ($types as $value => $label)
                                <label class="nd-choice">
                                    <input type="radio" name="type" value="{{ $value }}" @checked(old('type', $illustration->type) === $value) required>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="nd-field">
                        <label for="edit-desc">Description <span class="nd-hint">facultatif</span></label>
                        <textarea id="edit-desc" name="description" rows="5" maxlength="2000" class="neo-input nd-textarea" placeholder="Technique, inspiration, histoire de l'œuvre…">{{ old('description', $illustration->description) }}</textarea>
                    </div>

                    <div class="nd-field">
                        <label for="edit-tags">Mots-clés <span class="nd-hint">séparés par des virgules · {{ \App\Models\Illustration::MAX_TAGS }} au plus</span></label>
                        <input id="edit-tags" name="tags" type="text" class="neo-input" maxlength="300" placeholder="manga, Abidjan, nuit"
                            value="{{ old('tags', implode(', ', $illustration->tags ?? [])) }}">
                    </div>

                    <div class="nd-field">
                        <label for="edit-image">Remplacer l'image <span class="nd-hint">facultatif · {{ $maxImageMb }} Mo max</span></label>
                        <input id="edit-image" name="image" type="file" accept="image/*" class="nd-file">
                    </div>

                    <x-publication-fields :model="$illustration" id-prefix="edit" />

                    <x-rights-checkbox what="la nouvelle image" :required="false" hint="Obligatoire seulement si tu remplaces l'image." id-prefix="edit-rights" />

                    <button type="submit" class="neo-btn neo-btn--a1 nd-submit">Enregistrer</button>
                </div>
            </form>
        </div>

        <form method="POST" action="{{ route('illustrations.destroy', $illustration) }}" class="mt-10"
            onsubmit="return confirm('Supprimer définitivement « {{ e(addslashes($illustration->title)) }} » ?')">
            @csrf
            @method('DELETE')
            <input type="hidden" name="from_edit" value="1">
            <button type="submit" class="nd-delete" style="width: auto; padding: 0 18px;">Supprimer cette œuvre</button>
        </form>
    </div>
</x-world-layout>
