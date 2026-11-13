<x-admin-layout :title="'Demande de '.$joinRequest->name" intro="Fiche de la candidature, décision et notes internes.">
    <x-slot:actions>
        <a href="{{ route('admin.join-requests.index') }}" class="adm-btn adm-btn--ghost">← Toutes les demandes</a>
    </x-slot:actions>

    <div class="adm-stack">
        <section class="adm-card" aria-labelledby="jr-details-title">
            <div class="adm-row" style="justify-content: space-between;">
                <h2 id="jr-details-title" class="adm-card__title">Candidature</h2>
                <span class="adm-tag {{ ['nouvelle' => 'adm-tag--new', 'acceptee' => 'adm-tag--ok', 'refusee' => 'adm-tag--off'][$joinRequest->statut] ?? '' }}">{{ $joinRequest->statusLabel() }}</span>
            </div>
            <dl class="adm-stack" style="margin-top: 14px;">
                <div><dt class="adm-label">Nom</dt><dd>{{ $joinRequest->name }}</dd></div>
                <div><dt class="adm-label">E-mail</dt><dd><a href="mailto:{{ $joinRequest->email }}" class="adm-link" style="word-break: break-all;">{{ $joinRequest->email }}</a></dd></div>
                <div><dt class="adm-label">Discipline</dt><dd>{{ $joinRequest->disciplineLabel() }}</dd></div>
                <div>
                    <dt class="adm-label">Travail</dt>
                    <dd><a href="{{ $joinRequest->portfolio_url }}" class="adm-link" target="_blank" rel="noopener noreferrer nofollow" style="word-break: break-all;">{{ $joinRequest->portfolio_url }} ↗</a></dd>
                </div>
                @if ($joinRequest->message)
                    <div><dt class="adm-label">Message</dt><dd style="white-space: pre-line;">{{ $joinRequest->message }}</dd></div>
                @endif
                <div><dt class="adm-label">Reçue le</dt><dd>{{ $joinRequest->created_at?->locale('fr')->translatedFormat('j F Y à H:i') }}</dd></div>
                @if ($joinRequest->traite_le)
                    <div>
                        <dt class="adm-label">Traitée le</dt>
                        <dd>{{ $joinRequest->traite_le->locale('fr')->translatedFormat('j F Y à H:i') }}@if ($joinRequest->handler) par {{ $joinRequest->handler->name }}@endif</dd>
                    </div>
                @endif
                @if ($joinRequest->motif_refus)
                    <div><dt class="adm-label">Message de refus envoyé</dt><dd style="white-space: pre-line;">{{ $joinRequest->motif_refus }}</dd></div>
                @endif
                @if ($joinRequest->user)
                    <div>
                        <dt class="adm-label">Compte</dt>
                        <dd>
                            {{ $joinRequest->user->name }}
                            @can('manage-users')
                                · <a href="{{ route('admin.members.index', ['q' => $joinRequest->user->email]) }}" class="adm-link">Voir dans Membres</a>
                            @endcan
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        @if ($joinRequest->isNew())
            <div class="adm-grid adm-grid--2">
                <section class="adm-card" aria-labelledby="jr-accept-title">
                    <h2 id="jr-accept-title" class="adm-card__title">Accepter</h2>
                    @if ($existingUser)
                        <p class="adm-card__intro">
                            Un compte existe déjà avec cette adresse ({{ $existingUser->name }}). Il sera relié à la demande sans être recréé,
                            et la personne sera prévenue par e-mail.
                        </p>
                    @else
                        <p class="adm-card__intro">Le compte est créé (rôle membre, e-mail vérifié) et le candidat reçoit un lien pour choisir son mot de passe.</p>
                    @endif
                    <form method="POST" action="{{ route('admin.join-requests.accept', $joinRequest) }}" style="margin-top: 14px;">
                        @csrf
                        <div class="adm-field">
                            <label for="jr-type" class="adm-label">Type de compte</label>
                            <select id="jr-type" name="account_type" class="adm-select" @error('account_type') aria-invalid="true" aria-describedby="jr-type-error" @enderror>
                                @foreach ($accountTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('account_type', $joinRequest->suggestedAccountType()) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('account_type')<p id="jr-type-error" class="adm-error">{{ $message }}</p>@enderror
                            @if ($existingUser && ! $existingUser->isVisitor())
                                <p class="adm-help">Le type du compte existant ne change pas.</p>
                            @elseif ($joinRequest->discipline === 'les-deux')
                                <p class="adm-help">Le candidat fait les deux : choisissez l’espace principal.</p>
                            @endif
                        </div>
                        <div class="adm-actions">
                            <button type="submit" class="adm-btn">Accepter la demande</button>
                        </div>
                    </form>
                </section>

                <section class="adm-card" aria-labelledby="jr-refuse-title">
                    <h2 id="jr-refuse-title" class="adm-card__title">Refuser</h2>
                    <form method="POST" action="{{ route('admin.join-requests.refuse', $joinRequest) }}" style="margin-top: 14px;" x-data="{ n: {{ mb_strlen(old('motif_refus', '')) }} }">
                        @csrf
                        <div class="adm-field">
                            <label for="jr-motif" class="adm-label">
                                Message au candidat (facultatif)
                                <span class="adm-counter" aria-hidden="true" :data-over="n > 2000 ? '' : null" x-text="n + ' / 2000'"></span>
                            </label>
                            <textarea id="jr-motif" name="motif_refus" class="adm-textarea" maxlength="2000" x-on:input="n = $event.target.value.length"
                                aria-describedby="jr-motif-help @error('motif_refus') jr-motif-error @enderror" @error('motif_refus') aria-invalid="true" @enderror>{{ old('motif_refus') }}</textarea>
                            <p id="jr-motif-help" class="adm-help">Ajouté à l’e-mail de réponse. Restez bienveillant : la personne le lira telle quelle.</p>
                            @error('motif_refus')<p id="jr-motif-error" class="adm-error">{{ $message }}</p>@enderror
                        </div>
                        <label class="adm-check">
                            <input type="hidden" name="prevenir" value="0">
                            <input type="checkbox" name="prevenir" value="1" @checked(old('prevenir', '1') === '1')>
                            <span>Prévenir le candidat par e-mail</span>
                        </label>
                        <div class="adm-actions">
                            <button type="submit" class="adm-btn adm-btn--danger">Refuser la demande</button>
                        </div>
                    </form>
                </section>
            </div>
        @endif

        <section class="adm-card" aria-labelledby="jr-note-title">
            <h2 id="jr-note-title" class="adm-card__title">Note interne</h2>
            <p class="adm-card__intro">Visible uniquement par l’équipe.</p>
            <form method="POST" action="{{ route('admin.join-requests.note', $joinRequest) }}" style="margin-top: 14px;">
                @csrf
                @method('PATCH')
                <div class="adm-field">
                    <label for="jr-note" class="adm-label">Note</label>
                    <textarea id="jr-note" name="note_interne" class="adm-textarea" maxlength="2000"
                        @error('note_interne') aria-invalid="true" aria-describedby="jr-note-error" @enderror>{{ old('note_interne', $joinRequest->note_interne) }}</textarea>
                    @error('note_interne')<p id="jr-note-error" class="adm-error">{{ $message }}</p>@enderror
                </div>
                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--ghost">Enregistrer la note</button>
                </div>
            </form>
        </section>
    </div>
</x-admin-layout>
