<x-admin-layout title="Signalements" intro="Envoyés par le bouton « Signaler » des œuvres et des récits. Masquer une œuvre la retire du site et prévient son auteur avec le motif ; tous les signalements de l’œuvre sont alors traités ensemble.">
    <section class="adm-card" aria-labelledby="reports-title">
        <h2 id="reports-title" class="adm-card__title">
            {{ $groups->isEmpty() ? 'Aucun signalement en cours' : \App\Support\Fr::count($groups->count(), 'œuvre').' signalée'.($groups->count() > 1 ? 's' : '') }}
        </h2>

        @if ($groups->isEmpty())
            <p class="adm-empty">Aucun signalement en cours.</p>
        @else
            <ul class="adm-list" role="list">
                @foreach ($groups as $reports)
                    @php
                        $first = $reports->first();
                        $work = $first->reportable;
                        $url = \App\Support\Moderation::publicUrl($work);
                        $author = $work->authorUser();
                        $hasError = $errors->has('note') && (int) old('report') === $first->id;
                        $mailBody = "Bonjour ".($author?->name ?? '').",\n\nTon œuvre « ".$work->title." » a été signalée sur ".($collectif['nom'] ?? config('site.name'))." :\n"
                            .$reports->map(fn ($r) => '- '.$r->reasonLabel().($r->message ? ' : '.$r->message : ''))->unique()->implode("\n")
                            ."\n\nPeux-tu nous en dire plus ?\n\nL’équipe du collectif";
                        $mailto = $author?->email
                            ? 'mailto:'.$author->email.'?subject='.rawurlencode('Signalement de ton œuvre « '.$work->title.' »').'&body='.rawurlencode($mailBody)
                            : null;
                    @endphp
                    <li class="adc-item" id="signalement-{{ $first->id }}">
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="adc-item__media">
                            <img src="{{ \App\Support\Moderation::thumbnailUrl($work) }}" alt="Aperçu de « {{ $work->title }} » (s’ouvre dans un nouvel onglet)" class="adc-item__img" loading="lazy">
                        </a>
                        <div class="adc-item__body">
                            <p class="adc-kicker">{{ \App\Support\Moderation::kindLabel($work) }}</p>
                            <h3 class="adc-item__title">
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="adm-link">{{ $work->title }}<span class="sr-only"> (nouvel onglet)</span></a>
                            </h3>
                            <p class="adm-muted">
                                par {{ $author?->name ?? 'Auteur inconnu' }} · {{ \App\Support\Fr::count($reports->count(), 'signalement') }}
                                @unless ($work->isVisible())
                                    · <span class="adm-tag adm-tag--off">Déjà hors ligne</span>
                                @endunless
                            </p>
                            <ul class="adc-reports" role="list">
                                @foreach ($reports as $report)
                                    <li class="adc-report">
                                        <p class="adc-report__reason">{{ $report->reasonLabel() }}</p>
                                        @if ($report->message)
                                            <p class="adc-report__message">{{ $report->message }}</p>
                                        @endif
                                        <p class="adm-muted">
                                            {{ $report->user?->name ?? 'Visiteur' }}{{ $report->email ? ' ('.$report->email.')' : '' }}
                                            · <time datetime="{{ $report->created_at->toIso8601String() }}">{{ $report->created_at->locale('fr')->translatedFormat('j F à H\hi') }}</time>
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="adc-item__actions">
                            <form method="POST" action="{{ route('admin.reports.resolve', $first) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="action" value="dismiss">
                                <button type="submit" class="adm-btn adm-btn--ghost adc-full">Classer sans suite</button>
                            </form>
                            @if ($mailto)
                                <a href="{{ $mailto }}" class="adm-btn adm-btn--ghost adc-full">Contacter l’auteur</a>
                            @endif
                            <details class="adc-details" @if ($hasError) open @endif>
                                <summary class="adm-btn adm-btn--danger adc-full">Masquer l’œuvre…</summary>
                                <form method="POST" action="{{ route('admin.reports.resolve', $first) }}" class="adc-details__form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="report" value="{{ $first->id }}">
                                    @if ($work instanceof \App\Models\ComicChapter && ! $work->comic?->isOneShot())
                                        <fieldset class="adm-field">
                                            <legend class="adm-label">Que masquer ?</legend>
                                            <label class="adm-check"><input type="radio" name="scope" value="chapitre" checked> Ce chapitre seulement</label>
                                            <label class="adm-check"><input type="radio" name="scope" value="serie"> Toute la série « {{ $work->comic?->titre }} »</label>
                                        </fieldset>
                                    @elseif ($work instanceof \App\Models\ComicChapter)
                                        <input type="hidden" name="scope" value="serie">
                                    @endif
                                    <label for="report-note-{{ $first->id }}" class="adm-label">Motif <span class="adm-muted">(envoyé à l’auteur)</span></label>
                                    <textarea id="report-note-{{ $first->id }}" name="note" rows="3" required maxlength="500" class="adm-textarea"
                                        @if ($hasError) aria-invalid="true" aria-describedby="report-note-{{ $first->id }}-error" @endif>{{ $hasError ? old('note') : 'Retirée suite à un signalement : '.mb_strtolower($first->reasonLabel()).'.' }}</textarea>
                                    @if ($hasError)
                                        <p id="report-note-{{ $first->id }}-error" class="adm-error">{{ $errors->first('note') }}</p>
                                    @endif
                                    <button type="submit" class="adm-btn adm-btn--danger">Masquer du site</button>
                                </form>
                            </details>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($recent->isNotEmpty())
        <section class="adm-card" aria-labelledby="recent-title">
            <h2 id="recent-title" class="adm-card__title">Traités récemment</h2>
            <ul class="adm-list" role="list">
                @foreach ($recent as $report)
                    <li class="adm-row">
                        <span>{{ $report->reportable->title }}</span>
                        <span class="adm-tag {{ $report->status === \App\Models\Report::ACTIONED ? 'adm-tag--off' : 'adm-tag--ok' }}">
                            {{ $report->status === \App\Models\Report::ACTIONED ? 'Œuvre masquée' : 'Classé sans suite' }}
                        </span>
                        @if ($report->resolved_at)
                            <span class="adm-muted">le {{ $report->resolved_at->locale('fr')->translatedFormat('j F Y') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-admin-layout>
