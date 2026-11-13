@php
    use App\Support\Fr;
    use App\Support\SongeSky;

    // Formulaire concerné par les erreurs de validation (plusieurs formulaires sur la page).
    $formId = old('form_id');
    $fieldError = fn (string $form, string $field) => $formId === $form ? $errors->first($field) : null;
@endphp

<x-admin-layout title="Types & genres" intro="Types d’illustration (galerie et envoi) et genres littéraires (ciels de l’univers Songe). Un élément utilisé ne se supprime pas : désactivez-le, il ne sera plus proposé à l’envoi mais les œuvres existantes gardent leur libellé.">
    <x-slot:actions>
        <a href="{{ route('admin.taxonomy.genres.create') }}" class="adm-btn">Ajouter un genre</a>
    </x-slot:actions>

    {{-- ===== Types d'illustration ===== --}}
    <section class="adm-card" aria-labelledby="types-titre">
        <h2 id="types-titre" class="adm-card__title">Types d’illustration</h2>
        <p class="adm-card__intro adm-muted">
            Proposés à l’envoi et en filtres dans la galerie, dans cet ordre. « Autre » est toujours proposé en dernier :
            il regroupe aussi les œuvres sans type ou d’un type désactivé
            ({{ Fr::count($otherCount, 'œuvre') }} actuellement).
        </p>

        @if ($types->isEmpty())
            <p class="adm-empty">Aucun type : seul « Autre » est proposé.</p>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Ordre</th>
                            <th scope="col">Type</th>
                            <th scope="col">Œuvres</th>
                            <th scope="col">État</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($types as $type)
                            @php
                                $used = (int) ($typeUsage[$type->slug] ?? 0);
                                $form = 'type-'.$type->id;
                            @endphp
                            <tr>
                                <td>
                                    <div class="adm-row">
                                        <span aria-hidden="true">{{ $type->ordre }}</span>
                                        <form method="POST" action="{{ route('admin.taxonomy.types.move', $type) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm" @disabled($loop->first)>
                                                <span aria-hidden="true">↑</span><span class="sr-only">Monter « {{ $type->nom }} »</span>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.taxonomy.types.move', $type) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm" @disabled($loop->last)>
                                                <span aria-hidden="true">↓</span><span class="sr-only">Descendre « {{ $type->nom }} »</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $type->nom }}</strong>
                                    <br><span class="adm-muted">{{ $type->slug }}</span>
                                </td>
                                <td>{{ $used }}</td>
                                <td>
                                    <span class="adm-tag {{ $type->actif ? 'adm-tag--ok' : 'adm-tag--off' }}">{{ $type->actif ? 'Actif' : 'Désactivé' }}</span>
                                </td>
                                <td>
                                    <details @if ($formId === $form) open @endif>
                                        <summary class="adm-btn adm-btn--sm">Modifier<span class="sr-only"> « {{ $type->nom }} »</span></summary>
                                        <form method="POST" action="{{ route('admin.taxonomy.types.update', $type) }}" class="adm-stack" style="margin-top: 12px; min-width: 240px;">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="form_id" value="{{ $form }}">
                                            <div class="adm-field">
                                                <label class="adm-label" for="{{ $form }}-nom">Nom</label>
                                                <input id="{{ $form }}-nom" name="nom" type="text" class="adm-input" maxlength="60" required
                                                    value="{{ $formId === $form ? old('nom') : $type->nom }}"
                                                    @if ($fieldError($form, 'nom')) aria-invalid="true" aria-describedby="{{ $form }}-nom-erreur" @endif>
                                                @if ($fieldError($form, 'nom'))
                                                    <p id="{{ $form }}-nom-erreur" class="adm-error">{{ $fieldError($form, 'nom') }}</p>
                                                @endif
                                                <p class="adm-help">Adresse figée : <code>{{ $type->slug }}</code></p>
                                            </div>
                                            <div class="adm-field">
                                                <label class="adm-label" for="{{ $form }}-ordre">Ordre</label>
                                                <input id="{{ $form }}-ordre" name="ordre" type="number" min="0" max="9999" class="adm-input" inputmode="numeric"
                                                    value="{{ $formId === $form ? old('ordre') : $type->ordre }}"
                                                    @if ($fieldError($form, 'ordre')) aria-invalid="true" aria-describedby="{{ $form }}-ordre-erreur" @endif>
                                                @if ($fieldError($form, 'ordre'))
                                                    <p id="{{ $form }}-ordre-erreur" class="adm-error">{{ $fieldError($form, 'ordre') }}</p>
                                                @endif
                                            </div>
                                            <input type="hidden" name="actif" value="0">
                                            <label class="adm-check" for="{{ $form }}-actif">
                                                <input id="{{ $form }}-actif" type="checkbox" name="actif" value="1" @checked($formId === $form ? old('actif') : $type->actif)>
                                                <span>Actif <span class="adm-muted">(proposé à l’envoi et dans les filtres)</span></span>
                                            </label>
                                            <div class="adm-actions">
                                                <button type="submit" class="adm-btn adm-btn--sm">Enregistrer</button>
                                            </div>
                                        </form>
                                    </details>
                                    @if ($used === 0)
                                        <form method="POST" action="{{ route('admin.taxonomy.types.destroy', $type) }}" style="margin-top: 8px;"
                                            onsubmit="return confirm({{ \Illuminate\Support\Js::from('Supprimer le type « '.$type->nom.' » ?') }});">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Supprimer<span class="sr-only"> « {{ $type->nom }} »</span></button>
                                        </form>
                                    @else
                                        <p class="adm-muted" style="margin-top: 8px;">Utilisé : désactivez-le plutôt que de le supprimer.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.taxonomy.types.store') }}" class="adm-stack" style="margin-top: 20px;" aria-labelledby="nouveau-type-titre">
            @csrf
            <input type="hidden" name="form_id" value="type-new">
            <h3 id="nouveau-type-titre" class="adm-label">Ajouter un type</h3>
            <div class="adm-grid adm-grid--2">
                <div class="adm-field">
                    <label class="adm-label" for="type-new-nom">Nom</label>
                    <input id="type-new-nom" name="nom" type="text" class="adm-input" maxlength="60" required placeholder="Ex. Bande dessinée"
                        value="{{ $formId === 'type-new' ? old('nom') : '' }}"
                        aria-describedby="type-new-nom-aide{{ $fieldError('type-new', 'nom') ? ' type-new-nom-erreur' : '' }}"
                        @if ($fieldError('type-new', 'nom')) aria-invalid="true" @endif>
                    <p id="type-new-nom-aide" class="adm-help">L’adresse (slug) est calculée à partir du nom, puis ne change plus.</p>
                    @if ($fieldError('type-new', 'nom'))
                        <p id="type-new-nom-erreur" class="adm-error">{{ $fieldError('type-new', 'nom') }}</p>
                    @endif
                </div>
                <div class="adm-field">
                    <label class="adm-label" for="type-new-ordre">Ordre <span class="adm-muted">(facultatif : à la fin)</span></label>
                    <input id="type-new-ordre" name="ordre" type="number" min="0" max="9999" class="adm-input" inputmode="numeric"
                        value="{{ $formId === 'type-new' ? old('ordre') : '' }}"
                        @if ($fieldError('type-new', 'ordre')) aria-invalid="true" aria-describedby="type-new-ordre-erreur" @endif>
                    @if ($fieldError('type-new', 'ordre'))
                        <p id="type-new-ordre-erreur" class="adm-error">{{ $fieldError('type-new', 'ordre') }}</p>
                    @endif
                </div>
            </div>
            <input type="hidden" name="actif" value="1">
            <div class="adm-actions">
                <button type="submit" class="adm-btn">Ajouter le type</button>
            </div>
        </form>
    </section>

    {{-- ===== Genres littéraires ===== --}}
    <section class="adm-card" aria-labelledby="genres-titre">
        <div class="adm-row" style="justify-content: space-between;">
            <h2 id="genres-titre" class="adm-card__title">Genres littéraires</h2>
            <a href="{{ route('admin.taxonomy.genres.create') }}" class="adm-btn adm-btn--sm">Ajouter un genre</a>
        </div>
        <p class="adm-card__intro adm-muted">
            Chaque genre est un ciel de la page Écrivains : couleurs de jour et de nuit, phrase d’accroche et emblème.
            Proposés aux écrivains dans cet ordre.
        </p>

        @if ($genres->isEmpty())
            <p class="adm-empty">Aucun genre pour le moment.</p>
        @else
            <ul class="adm-list" role="list">
                @foreach ($genres as $genre)
                    @php
                        $sky = SongeSky::get($genre->slug);
                        $used = (int) ($genreUsage[$genre->slug] ?? 0);
                    @endphp
                    <li>
                        <div class="adm-row" style="align-items: flex-start; gap: 16px;">
                            <span class="adm-row" style="gap: 6px;" aria-hidden="true">
                                <span style="display: grid; place-items: center; width: 64px; height: 64px; border-radius: 14px; background: {{ $sky['sky_day'] }}; color: {{ $sky['ink_day'] }};">
                                    <x-genre-emblem :genre="$genre->slug" style="width: 44px; height: 44px;" />
                                </span>
                                <span style="display: grid; place-items: center; width: 64px; height: 64px; border-radius: 14px; background: {{ $sky['sky_night'] }}; color: {{ $sky['glow'] }};">
                                    <x-genre-emblem :genre="$genre->slug" style="width: 44px; height: 44px;" />
                                </span>
                            </span>
                            <div style="flex: 1 1 220px; min-width: 0;">
                                <p>
                                    <strong>{{ $genre->nom }}</strong>
                                    <span class="adm-tag {{ $genre->actif ? 'adm-tag--ok' : 'adm-tag--off' }}">{{ $genre->actif ? 'Actif' : 'Désactivé' }}</span>
                                </p>
                                <p class="adm-muted">{{ $genre->slug }} · ordre {{ $genre->ordre }} · {{ Fr::count($used, 'récit') }}</p>
                                @if ($genre->phrase_accroche)
                                    <p class="adm-muted">« {{ $genre->phrase_accroche }} »</p>
                                @endif
                            </div>
                            <div class="adm-row">
                                <form method="POST" action="{{ route('admin.taxonomy.genres.move', $genre) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm" @disabled($loop->first)>
                                        <span aria-hidden="true">↑</span><span class="sr-only">Monter « {{ $genre->nom }} »</span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.taxonomy.genres.move', $genre) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm" @disabled($loop->last)>
                                        <span aria-hidden="true">↓</span><span class="sr-only">Descendre « {{ $genre->nom }} »</span>
                                    </button>
                                </form>
                                <a href="{{ route('admin.taxonomy.genres.edit', $genre) }}" class="adm-btn adm-btn--sm">Modifier<span class="sr-only"> « {{ $genre->nom }} »</span></a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-admin-layout>
