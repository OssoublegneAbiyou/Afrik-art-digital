@php($createErrors = $errors->getBag('create'))
<x-admin-layout title="Membres" intro="Comptes du collectif : rôles d’administration, désactivation, création et suppression.">
    <div class="adm-stack">
        <details class="adm-card" @if ($createErrors->any()) open @endif>
            <summary class="adm-card__title" style="cursor: pointer; min-height: 44px; display: flex; align-items: center;">Créer un compte</summary>
            <p class="adm-card__intro">Le membre reçoit un e-mail avec un lien pour choisir lui-même son mot de passe. Aucun mot de passe n’est envoyé en clair.</p>

            <form method="POST" action="{{ route('admin.members.store') }}" style="margin-top: 16px;" novalidate>
                @csrf
                <div class="adm-grid adm-grid--2">
                    <div class="adm-field">
                        <label for="create-name" class="adm-label">Nom</label>
                        <input id="create-name" name="name" type="text" class="adm-input" value="{{ old('name') }}" required maxlength="255" autocomplete="off"
                            @if ($createErrors->has('name')) aria-invalid="true" aria-describedby="create-name-error" @endif>
                        @if ($createErrors->has('name'))<p id="create-name-error" class="adm-error">{{ $createErrors->first('name') }}</p>@endif
                    </div>
                    <div class="adm-field" style="margin-top: 0;">
                        <label for="create-email" class="adm-label">E-mail</label>
                        <input id="create-email" name="email" type="email" class="adm-input" value="{{ old('email') }}" required maxlength="255" autocomplete="off"
                            @if ($createErrors->has('email')) aria-invalid="true" aria-describedby="create-email-error" @endif>
                        @if ($createErrors->has('email'))<p id="create-email-error" class="adm-error">{{ $createErrors->first('email') }}</p>@endif
                    </div>
                    <div class="adm-field" style="margin-top: 0;">
                        <label for="create-type" class="adm-label">Type de compte</label>
                        <select id="create-type" name="account_type" class="adm-select" @if ($createErrors->has('account_type')) aria-invalid="true" aria-describedby="create-type-error" @endif>
                            @foreach ($accountTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('account_type', 'artist') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($createErrors->has('account_type'))<p id="create-type-error" class="adm-error">{{ $createErrors->first('account_type') }}</p>@endif
                    </div>
                    <div class="adm-field" style="margin-top: 0;">
                        <label for="create-role" class="adm-label">Rôle</label>
                        <select id="create-role" name="role" class="adm-select" @if ($createErrors->has('role')) aria-invalid="true" aria-describedby="create-role-error" @endif>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', \App\Models\User::ROLE_MEMBER) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($createErrors->has('role'))<p id="create-role-error" class="adm-error">{{ $createErrors->first('role') }}</p>@endif
                    </div>
                </div>
                <div class="adm-actions">
                    <button type="submit" class="adm-btn">Créer et envoyer l’invitation</button>
                </div>
            </form>
        </details>

        <section class="adm-card" aria-labelledby="adm-members-title">
            <h2 id="adm-members-title" class="adm-card__title">Comptes</h2>

            <form method="GET" action="{{ route('admin.members.index') }}" class="adm-filters" role="search" style="margin-top: 14px;">
                <div class="adm-field">
                    <label for="members-q" class="adm-label">Rechercher un compte</label>
                    <input id="members-q" name="q" type="search" class="adm-input" value="{{ $filters['q'] }}" placeholder="Nom ou e-mail">
                </div>
                <div class="adm-field" style="margin-top: 0;">
                    <label for="members-role" class="adm-label">Rôle</label>
                    <select id="members-role" name="role" class="adm-select">
                        <option value="">Tous</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="adm-field" style="margin-top: 0;">
                    <label for="members-type" class="adm-label">Type de compte</label>
                    <select id="members-type" name="type" class="adm-select">
                        <option value="">Tous</option>
                        @foreach ($accountTypes as $value => $label)
                            <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="adm-field" style="margin-top: 0;">
                    <label for="members-etat" class="adm-label">État</label>
                    <select id="members-etat" name="etat" class="adm-select">
                        <option value="">Tous</option>
                        <option value="actifs" @selected($filters['etat'] === 'actifs')>Actifs</option>
                        <option value="desactives" @selected($filters['etat'] === 'desactives')>Désactivés</option>
                    </select>
                </div>
                <button type="submit" class="adm-btn adm-btn--ghost">Filtrer</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.members.index') }}" class="adm-link">Effacer les filtres</a>
                @endif
            </form>

            <p class="adm-muted" role="status">{{ \App\Support\Fr::count($members->total(), 'compte trouvé', 'comptes trouvés') }}</p>

            @if ($members->isEmpty())
                <p class="adm-empty">Aucun compte ne correspond à cette recherche.</p>
            @else
                <div class="adm-table-wrap" style="margin-top: 10px;">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th scope="col">Membre</th>
                                <th scope="col">Œuvres</th>
                                <th scope="col">Rôle</th>
                                <th scope="col">Compte</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                @php($isSelf = auth()->user()->is($member))
                                @php($lastAdmin = $member->role === \App\Models\User::ROLE_ADMIN && ! $member->isDeactivated() && $activeAdmins <= 1)
                                <tr>
                                    <td>
                                        <strong>{{ $member->name }}</strong>
                                        @if ($isSelf)<span class="adm-tag">vous</span>@endif
                                        <div class="adm-muted" style="word-break: break-all;">{{ $member->email }}</div>
                                        <div class="adm-row" style="margin-top: 6px;">
                                            <span class="adm-tag">{{ $accountTypes[$member->account_type] ?? 'Type à choisir' }}</span>
                                            @if ($member->isDeactivated())
                                                <span class="adm-tag adm-tag--off">Désactivé depuis le {{ $member->deactivated_at->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php($pendingCount = $member->illustrations_pending_count + $member->documents_pending_count)
                                        @if ($member->artist)
                                            <div>{{ \App\Support\Fr::count($member->illustrations_count, 'illustration') }}</div>
                                            <a href="{{ route('public.artist', $member->artist) }}" class="adm-link">Voir le studio<span class="sr-only"> de {{ $member->name }}</span></a>
                                        @endif
                                        @if ($member->writer)
                                            <div>{{ \App\Support\Fr::count($member->documents_count, 'récit') }}</div>
                                            <a href="{{ route('public.writer', $member->writer) }}" class="adm-link">Voir les récits<span class="sr-only"> de {{ $member->name }}</span></a>
                                        @endif
                                        @if (! $member->artist && ! $member->writer)
                                            <span class="adm-muted">Aucun espace de création</span>
                                        @endif
                                        @if ($pendingCount > 0)
                                            <div style="margin-top: 6px;">
                                                @if (Route::has('admin.moderation.index'))
                                                    <a href="{{ route('admin.moderation.index') }}" class="adm-tag adm-tag--pending">{{ $pendingCount }} à valider</a>
                                                @else
                                                    <span class="adm-tag adm-tag--pending">{{ $pendingCount }} à valider</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.members.role', $member) }}" class="adm-row">
                                            @csrf
                                            @method('PATCH')
                                            <label for="role-{{ $member->id }}" class="sr-only">Rôle de {{ $member->name }}</label>
                                            <select id="role-{{ $member->id }}" name="role" class="adm-select" style="width: auto;" @if ($lastAdmin) aria-describedby="last-admin-{{ $member->id }}" @endif>
                                                @foreach ($roles as $value => $label)
                                                    <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm">Changer<span class="sr-only"> le rôle de {{ $member->name }}</span></button>
                                        </form>
                                        @if ($lastAdmin)
                                            <p id="last-admin-{{ $member->id }}" class="adm-help">Dernier admin actif : ce rôle ne peut pas être retiré.</p>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="adm-row">
                                            @if ($member->isDeactivated())
                                                <form method="POST" action="{{ route('admin.members.reactivate', $member) }}">
                                                    @csrf
                                                    <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm">Réactiver<span class="sr-only"> {{ $member->name }}</span></button>
                                                </form>
                                            @elseif (! $isSelf && ! $lastAdmin)
                                                <form method="POST" action="{{ route('admin.members.deactivate', $member) }}">
                                                    @csrf
                                                    <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm">Désactiver<span class="sr-only"> {{ $member->name }}</span></button>
                                                </form>
                                            @endif
                                            @if (! $isSelf && ! $lastAdmin)
                                                <form method="POST" action="{{ route('admin.members.destroy', $member) }}"
                                                    onsubmit="return confirm(@js('Supprimer définitivement le compte de '.$member->name.' et toutes ses œuvres ?'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Supprimer<span class="sr-only"> {{ $member->name }}</span></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="adm-pagination">{{ $members->links('admin.partials.pager') }}</div>
            @endif
        </section>
    </div>
</x-admin-layout>
