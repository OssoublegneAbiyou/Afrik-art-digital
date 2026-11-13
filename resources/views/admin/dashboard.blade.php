<x-admin-layout title="Tableau de bord" intro="Ce qui attend l’équipe aujourd’hui, et la vie du collectif en chiffres.">
    <div class="adm-stack">
        <section class="adm-card" aria-labelledby="adm-todo-title">
            <h2 id="adm-todo-title" class="adm-card__title">À traiter</h2>
            <div class="adm-grid adm-grid--3" style="margin-top: 14px;">
                @foreach ($todo as [$label, $value, $route])
                    @if (Route::has($route))
                        <a href="{{ route($route) }}" class="adm-stat {{ $value > 0 ? 'adm-stat--alert' : '' }}">
                            <div class="adm-stat__value">{{ $value }}</div>
                            <div class="adm-stat__label">{{ $label }}</div>
                        </a>
                    @else
                        <div class="adm-stat {{ $value > 0 ? 'adm-stat--alert' : '' }}">
                            <div class="adm-stat__value">{{ $value }}</div>
                            <div class="adm-stat__label">{{ $label }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        <section class="adm-card" aria-labelledby="adm-collectif-title">
            <h2 id="adm-collectif-title" class="adm-card__title">Le collectif</h2>
            <div class="adm-grid adm-grid--3" style="margin-top: 14px;">
                @foreach ($collective as [$label, $value, $route])
                    @if ($route && Route::has($route) && auth()->user()->can('manage-users'))
                        <a href="{{ route($route) }}" class="adm-stat">
                            <div class="adm-stat__value">{{ $value }}</div>
                            <div class="adm-stat__label">{{ $label }}</div>
                        </a>
                    @else
                        <div class="adm-stat">
                            <div class="adm-stat__value">{{ $value }}</div>
                            <div class="adm-stat__label">{{ $label }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        <div class="adm-grid adm-grid--2">
            @if ($shortcuts->isNotEmpty())
                <section class="adm-card" aria-labelledby="adm-shortcuts-title">
                    <h2 id="adm-shortcuts-title" class="adm-card__title">Raccourcis</h2>
                    <ul class="adm-list" style="margin-top: 8px;">
                        @foreach ($shortcuts as [$route, $label])
                            <li><a href="{{ route($route) }}" class="adm-link">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @can('manage-users')
                <section class="adm-card" aria-labelledby="adm-logs-title">
                    <h2 id="adm-logs-title" class="adm-card__title">Dernières modifications</h2>
                    @if ($logs->isEmpty())
                        <p class="adm-empty">Aucune modification enregistrée pour l’instant.</p>
                    @else
                        <ul class="adm-list" style="margin-top: 8px;">
                            @foreach ($logs as $log)
                                <li>
                                    <strong>{{ \App\Support\AdminLogPresenter::actionLabel($log->action) }}</strong>
                                    @if ($target = \App\Support\AdminLogPresenter::target($log))
                                        <span class="adm-muted">· {{ $target }}</span>
                                    @endif
                                    <p class="adm-muted">
                                        {{ $log->user?->name ?? 'Système' }} ·
                                        <time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->locale('fr')->diffForHumans() }}</time>
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                        @if (Route::has('admin.logs.index'))
                            <p class="adm-actions"><a href="{{ route('admin.logs.index') }}" class="adm-btn adm-btn--ghost adm-btn--sm">Tout le journal</a></p>
                        @endif
                    @endif
                </section>
            @endcan
        </div>
    </div>
</x-admin-layout>
