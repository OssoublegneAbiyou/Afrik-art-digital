<x-admin-layout title="Demandes d’adhésion" intro="Les candidatures envoyées depuis la page « À propos ». Accepter crée le compte et envoie au candidat un lien pour choisir son mot de passe.">
    @php($tabs = ['nouvelle' => 'Nouvelles', 'acceptee' => 'Acceptées', 'refusee' => 'Refusées', 'toutes' => 'Toutes'])
    <nav class="adm-tabs" aria-label="Filtrer par statut">
        @foreach ($tabs as $value => $label)
            @php($count = $value === 'toutes' ? $total : ($counts[$value] ?? 0))
            <a href="{{ route('admin.join-requests.index', ['statut' => $value]) }}" class="adm-tab" @if ($status === $value) aria-current="page" @endif>
                {{ $label }}&nbsp;·&nbsp;{{ $count }}
            </a>
        @endforeach
    </nav>

    <section class="adm-card" aria-label="Liste des demandes">
        @if ($requests->isEmpty())
            <p class="adm-empty">
                {{ $status === 'nouvelle' ? 'Aucune nouvelle demande. Tout est traité !' : 'Aucune demande dans cette liste.' }}
            </p>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Candidat</th>
                            <th scope="col">Discipline</th>
                            <th scope="col">Reçue le</th>
                            <th scope="col">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.join-requests.show', $item) }}" class="adm-link">{{ $item->name }}</a>
                                    <div class="adm-muted" style="word-break: break-all;">{{ $item->email }}</div>
                                </td>
                                <td>{{ $item->disciplineLabel() }}</td>
                                <td><time datetime="{{ $item->created_at?->toIso8601String() }}">{{ $item->created_at?->format('d/m/Y') }}</time></td>
                                <td>
                                    <span class="adm-tag {{ ['nouvelle' => 'adm-tag--new', 'acceptee' => 'adm-tag--ok', 'refusee' => 'adm-tag--off'][$item->statut] ?? '' }}">{{ $item->statusLabel() }}</span>
                                    @if ($item->traite_le)
                                        <div class="adm-muted">le {{ $item->traite_le->format('d/m/Y') }}@if ($item->handler) par {{ $item->handler->name }}@endif</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="adm-pagination">{{ $requests->links('admin.partials.pager') }}</div>
        @endif
    </section>
</x-admin-layout>
