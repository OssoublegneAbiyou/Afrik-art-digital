@php
    $formAction = $editing ? route('admin.announcements.update', $editing) : route('admin.announcements.store');
    $value = fn (string $field) => old($field, $editing?->{$field});
    $dateValue = fn (string $field) => old($field, $editing?->{$field}?->format('Y-m-d\TH:i'));
    $texte = (string) $value('texte');
@endphp

<x-admin-layout title="Annonces" intro="Bandeau fin affiché en haut de toutes les pages du site (une seule annonce à la fois : la plus récente parmi celles actives et dans leur période). Les visiteurs peuvent la fermer.">
    <section class="adm-card" aria-labelledby="current-title">
        <h2 id="current-title" class="adm-card__title">En ce moment sur le site</h2>
        @if ($current)
            <div class="aad-annonce aad-annonce--apercu" aria-hidden="true">
                <p class="aad-annonce__text">{{ $current->texte }} @if ($current->lien)<span>→</span>@endif</p>
            </div>
        @else
            <p class="adm-muted">Aucune annonce affichée.</p>
        @endif
    </section>

    <section class="adm-card" aria-labelledby="form-title" id="formulaire">
        <h2 id="form-title" class="adm-card__title">{{ $editing ? 'Modifier l’annonce' : 'Nouvelle annonce' }}</h2>

        <form method="POST" action="{{ $formAction }}" class="adc-form">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="adm-field" x-data="{ n: {{ mb_strlen($texte) }} }">
                <label for="texte" class="adm-label">
                    Texte
                    <span class="adm-counter" :data-over="n > {{ \App\Models\Annonce::TEXT_MAX }} ? '' : null" aria-hidden="true"><span x-text="n">{{ mb_strlen($texte) }}</span>/{{ \App\Models\Annonce::TEXT_MAX }}</span>
                </label>
                <input id="texte" name="texte" class="adm-input" required maxlength="{{ \App\Models\Annonce::TEXT_MAX }}" value="{{ $texte }}"
                    x-on:input="n = $event.target.value.length"
                    aria-describedby="texte-help @error('texte') texte-error @enderror" @error('texte') aria-invalid="true" @enderror>
                <p id="texte-help" class="adm-help">{{ \App\Models\Annonce::TEXT_MAX }} caractères au plus. Ex. : « Exposition du collectif samedi au Plateau, entrée libre ».</p>
                @error('texte')
                    <p id="texte-error" class="adm-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="adm-field">
                <label for="lien" class="adm-label">Lien <span class="adm-muted">(facultatif)</span></label>
                <input id="lien" name="lien" class="adm-input" maxlength="255" value="{{ $value('lien') }}" placeholder="https://… ou /a-propos" inputmode="url"
                    aria-describedby="lien-help @error('lien') lien-error @enderror" @error('lien') aria-invalid="true" @enderror>
                <p id="lien-help" class="adm-help">Adresse complète (https://…) ou chemin du site (/a-propos). Le texte de l’annonce devient cliquable.</p>
                @error('lien')
                    <p id="lien-error" class="adm-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="adm-grid adm-grid--2">
                <div class="adm-field">
                    <label for="debut" class="adm-label">Début <span class="adm-muted">(facultatif)</span></label>
                    <input id="debut" type="datetime-local" name="debut" class="adm-input" value="{{ $dateValue('debut') }}"
                        @error('debut') aria-invalid="true" aria-describedby="debut-error" @enderror>
                    @error('debut')
                        <p id="debut-error" class="adm-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="adm-field">
                    <label for="fin" class="adm-label">Fin <span class="adm-muted">(facultatif)</span></label>
                    <input id="fin" type="datetime-local" name="fin" class="adm-input" value="{{ $dateValue('fin') }}"
                        @error('fin') aria-invalid="true" aria-describedby="fin-error" @enderror>
                    @error('fin')
                        <p id="fin-error" class="adm-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="adm-help">Sans dates, l’annonce reste affichée tant qu’elle est active.</p>

            <div class="adm-field">
                <input type="hidden" name="actif" value="0">
                <label class="adm-check">
                    <input type="checkbox" name="actif" value="1" @checked(old('actif', $editing?->actif ?? true))>
                    <span>Active</span>
                </label>
            </div>

            <div class="adm-actions">
                <button type="submit" class="adm-btn">{{ $editing ? 'Enregistrer' : 'Créer l’annonce' }}</button>
                @if ($editing)
                    <a href="{{ route('admin.announcements.index') }}" class="adm-btn adm-btn--ghost">Annuler</a>
                @endif
            </div>
        </form>
    </section>

    <section class="adm-card" aria-labelledby="list-title">
        <h2 id="list-title" class="adm-card__title">Toutes les annonces</h2>
        @if ($annonces->isEmpty())
            <p class="adm-empty">Aucune annonce pour le moment.</p>
        @else
            <ul class="adm-list" role="list">
                @foreach ($annonces as $annonce)
                    @php($status = $annonce->statusLabel())
                    <li class="adc-annonce-row">
                        <div class="adc-annonce-row__text">
                            <p>
                                <span class="adm-tag {{ $current?->is($annonce) ? 'adm-tag--ok' : ($status === 'Programmée' ? 'adm-tag--pending' : 'adm-tag--off') }}">
                                    {{ $current?->is($annonce) ? 'Affichée' : ($status === 'En ligne' ? 'En attente (une plus récente est affichée)' : $status) }}
                                </span>
                            </p>
                            <p class="adc-annonce-row__texte">{{ $annonce->texte }}</p>
                            <p class="adm-muted">
                                @if ($annonce->lien) Lien : {{ $annonce->lien }} · @endif
                                {{ $annonce->debut ? 'du '.$annonce->debut->locale('fr')->translatedFormat('j F Y à H\hi') : 'dès maintenant' }}
                                {{ $annonce->fin ? 'au '.$annonce->fin->locale('fr')->translatedFormat('j F Y à H\hi') : 'sans date de fin' }}
                            </p>
                        </div>
                        <div class="adm-row">
                            <a href="{{ route('admin.announcements.index', ['modifier' => $annonce->id]) }}#formulaire" class="adm-btn adm-btn--ghost adm-btn--sm">Modifier<span class="sr-only"> l’annonce « {{ \Illuminate\Support\Str::limit($annonce->texte, 40) }} »</span></a>
                            <form method="POST" action="{{ route('admin.announcements.toggle', $annonce) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm">{{ $annonce->actif ? 'Désactiver' : 'Activer' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $annonce) }}" onsubmit="return confirm('Supprimer cette annonce ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Supprimer</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-admin-layout>
