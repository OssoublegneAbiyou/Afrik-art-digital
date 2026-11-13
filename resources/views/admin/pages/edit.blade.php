<x-admin-layout :title="'Modifier : '.$page->titre" intro="Texte simple ou HTML léger : titres (h2, h3), paragraphes, gras, italique, listes, citations et liens. Le reste est retiré à l’enregistrement. « {nom} » est remplacé par le nom du collectif.">
    <x-slot:actions>
        <a href="{{ route('admin.pages.index') }}" class="adm-btn adm-btn--ghost">← Toutes les pages</a>
        <a href="{{ $page->url() }}" class="adm-btn adm-btn--ghost" target="_blank" rel="noopener">Voir sur le site<span class="sr-only"> (nouvel onglet)</span> ↗</a>
    </x-slot:actions>

    <div class="adm-grid adm-grid--2">
        <section class="adm-card" aria-labelledby="edit-title">
            <h2 id="edit-title" class="adm-card__title">Contenu</h2>

            <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="adc-form">
                @csrf
                @method('PUT')

                <div class="adm-field">
                    <label for="titre" class="adm-label">Titre</label>
                    <input id="titre" name="titre" class="adm-input" required maxlength="120" value="{{ old('titre', $page->titre) }}"
                        @error('titre') aria-invalid="true" aria-describedby="titre-error" @enderror>
                    @error('titre')
                        <p id="titre-error" class="adm-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="adm-field">
                    <label for="contenu" class="adm-label">Contenu</label>
                    <textarea id="contenu" name="contenu" class="adm-textarea adc-code" rows="20" required
                        aria-describedby="contenu-help @error('contenu') contenu-error @enderror"
                        @error('contenu') aria-invalid="true" @enderror>{{ old('contenu', $page->contenu) }}</textarea>
                    <p id="contenu-help" class="adm-help">Exemple : &lt;h2&gt;Titre&lt;/h2&gt; &lt;p&gt;Paragraphe avec un &lt;a href="https://…"&gt;lien&lt;/a&gt;.&lt;/p&gt; — une ligne vide crée un nouveau paragraphe si vous écrivez sans HTML.</p>
                    @error('contenu')
                        <p id="contenu-error" class="adm-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="adm-field" x-data="{ n: {{ mb_strlen((string) old('meta_description', $page->meta_description)) }} }">
                    <label for="meta_description" class="adm-label">
                        Description pour les moteurs de recherche
                        <span class="adm-counter" :data-over="n > 160 ? '' : null" aria-hidden="true"><span x-text="n">{{ mb_strlen((string) old('meta_description', $page->meta_description)) }}</span>/160</span>
                    </label>
                    <textarea id="meta_description" name="meta_description" class="adm-textarea" rows="3" maxlength="160"
                        x-on:input="n = $event.target.value.length"
                        aria-describedby="meta-help @error('meta_description') meta-error @enderror"
                        @error('meta_description') aria-invalid="true" @enderror>{{ old('meta_description', $page->meta_description) }}</textarea>
                    <p id="meta-help" class="adm-help">160 caractères au plus. Affichée sous le titre dans les résultats de recherche.</p>
                    @error('meta_description')
                        <p id="meta-error" class="adm-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="adm-field">
                    <input type="hidden" name="publie" value="0">
                    <label class="adm-check">
                        <input type="checkbox" name="publie" value="1" @checked(old('publie', $page->publie))>
                        <span>Publiée <span class="adm-muted">— visible sur le site et dans le pied de page</span></span>
                    </label>
                </div>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn">Enregistrer</button>
                </div>
            </form>
        </section>

        <section class="adm-card" aria-labelledby="preview-title">
            <h2 id="preview-title" class="adm-card__title">Aperçu de la version enregistrée</h2>
            <div class="pg-content adc-preview">
                {!! str_replace('{nom}', e($collectif['nom'] ?? config('site.name')), \App\Support\RichText::clean($page->contenu)) !!}
            </div>
        </section>
    </div>
</x-admin-layout>
