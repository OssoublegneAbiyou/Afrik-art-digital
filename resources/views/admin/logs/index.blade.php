<x-admin-layout title="Journal" intro="Toutes les modifications faites depuis l’administration, des plus récentes aux plus anciennes. Lecture seule.">
    <section class="adm-card" aria-labelledby="logs-title">
        <h2 id="logs-title" class="sr-only">Entrées du journal</h2>

        <form method="GET" action="{{ route('admin.logs.index') }}" class="adm-filters">
            <div class="adm-field">
                <label for="logs-action" class="adm-label">Action</label>
                <select id="logs-action" name="action" class="adm-select">
                    <option value="">Toutes</option>
                    @foreach ($actions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['action'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="adm-field" style="margin-top: 0;">
                <label for="logs-auteur" class="adm-label">Auteur</label>
                <select id="logs-auteur" name="auteur" class="adm-select">
                    <option value="">Tous</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->id }}" @selected($filters['auteur'] === (string) $author->id)>{{ $author->name }}</option>
                    @endforeach
                    <option value="systeme" @selected($filters['auteur'] === 'systeme')>Système (console, compte supprimé)</option>
                </select>
            </div>
            <div class="adm-field" style="margin-top: 0;">
                <label for="logs-du" class="adm-label">Du</label>
                <input id="logs-du" name="du" type="date" class="adm-input" value="{{ $filters['du'] }}">
            </div>
            <div class="adm-field" style="margin-top: 0;">
                <label for="logs-au" class="adm-label">Au</label>
                <input id="logs-au" name="au" type="date" class="adm-input" value="{{ $filters['au'] }}">
            </div>
            <button type="submit" class="adm-btn adm-btn--ghost">Filtrer</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.logs.index') }}" class="adm-link">Effacer les filtres</a>
            @endif
        </form>

        @if ($logs->isEmpty())
            <p class="adm-empty">Aucune entrée ne correspond.</p>
        @else
            <ul class="adm-list">
                @foreach ($logs as $log)
                    @php($changes = \App\Support\AdminLogPresenter::changes($log))
                    <li>
                        <div class="adm-row" style="justify-content: space-between;">
                            <div>
                                <strong>{{ \App\Support\AdminLogPresenter::actionLabel($log->action) }}</strong>
                                @if ($target = \App\Support\AdminLogPresenter::target($log))
                                    <span class="adm-muted">· {{ $target }}</span>
                                @endif
                            </div>
                            <span class="adm-muted">
                                {{ $log->user?->name ?? 'Système' }} ·
                                <time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('d/m/Y à H:i') }}</time>
                            </span>
                        </div>
                        @if ($changes)
                            <dl style="margin-top: 8px; display: grid; gap: 6px;">
                                @foreach ($changes as $change)
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px 10px; align-items: baseline;">
                                        <dt class="adm-muted" style="min-width: 8em;">{{ $change['key'] }}</dt>
                                        <dd style="display: flex; flex-wrap: wrap; gap: 6px; align-items: baseline; min-width: 0;">
                                            @if ($change['before'] !== null)
                                                <pre style="margin: 0; white-space: pre-wrap; word-break: break-word; font: inherit; color: var(--muted); text-decoration: line-through; text-decoration-color: rgba(255, 143, 191, .6);"><span class="sr-only">Avant : </span>{{ $change['before'] }}</pre>
                                            @endif
                                            @if ($change['before'] !== null && $change['after'] !== null)
                                                <span aria-hidden="true">→</span>
                                            @endif
                                            @if ($change['after'] !== null)
                                                <pre style="margin: 0; white-space: pre-wrap; word-break: break-word; font: inherit; color: var(--ink);"><span class="sr-only">Après : </span>{{ $change['after'] }}</pre>
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="adm-pagination">{{ $logs->links('admin.partials.pager') }}</div>
        @endif
    </section>
</x-admin-layout>
