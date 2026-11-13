@php
    /** @var \App\Models\Comic $comic */
    /** @var \App\Models\ComicChapter $chapter */
    use App\Models\ComicChapter;
    use App\Support\ComicStudio;
    use App\Support\Fr;

    $oneShot = $comic->isOneShot();
    $tz = ComicStudio::timezone();
    $action = old('action', $chapter->isScheduled() ? ComicChapter::STATUS_SCHEDULED : ComicChapter::STATUS_PUBLISHED);
    $scheduled = old('scheduled_at', $chapter->isScheduled() ? $chapter->published_at->copy()->setTimezone($tz)->format('Y-m-d\TH:i') : '');
    $min = now()->setTimezone($tz)->addMinutes(5)->format('Y-m-d\TH:i');
    $ready = $pages->filter->isReady();
@endphp

<x-world-layout world="neo" :title="'Publication · '.$comic->titre" description="Assistant de création d’un récit dessiné : la publication." robots-noindex>
    <div class="nd cs">
        <div class="w-container nd-body">
            <p class="cs-back"><a href="{{ route('comics.studio.manage', $comic) }}" class="cs-link">← {{ $oneShot ? 'Retour à « '.$comic->titre.' »' : 'Gérer la série' }}</a></p>
            <h1 class="nd-h2 font-bebas cs-h1">
                {{ $comic->titre }}@unless ($oneShot) <span class="cs-h1__sub">· Chapitre {{ $chapter->numberLabel() }}</span>@endunless
            </h1>

            @include('comics.studio._steps', ['current' => 3, 'comic' => $comic, 'chapter' => $chapter])
            @include('comics.studio._flash')

            <div class="cs-publish">
                <section class="neo-card nd-panel" aria-labelledby="cs-preview-title">
                    <div class="neo-card__head">
                        <h2 id="cs-preview-title" class="neo-card__name">Aperçu</h2>
                        <span class="neo-card__tag">{{ Fr::count($pages->count(), 'page') }}</span>
                    </div>
                    <div class="nd-panel__body cs-preview">
                        @if ($comic->coverUrl())
                            <img src="{{ $comic->coverUrl() }}" alt="Couverture de « {{ $comic->titre }} »" class="cs-preview__cover" width="160">
                        @endif
                        <div>
                            <p class="nd-kicker">{{ $comic->typeLabel() }}@if ($comic->genreLabel()) · {{ $comic->genreLabel() }}@endif</p>
                            <p class="cs-preview__status">Statut actuel : <strong>{{ ComicStudio::chapterStatus($chapter) }}</strong></p>
                            @if ($chapter->isRejected() && $chapter->moderation_note)
                                <p class="nd-hint">Motif de l’équipe : {{ $chapter->moderation_note }}</p>
                            @endif
                            @if ($previewUrl && $ready->isNotEmpty())
                                <a href="{{ $previewUrl }}" class="neo-btn neo-btn--a2" target="_blank" rel="noopener">Aperçu dans le lecteur<span class="sr-only"> (nouvel onglet)</span> ↗</a>
                                <p class="nd-hint">L’aperçu n’est visible que par toi tant que ce n’est pas publié.</p>
                            @endif
                        </div>
                        @if ($ready->isNotEmpty())
                            <ol class="cs-strip" role="list" aria-label="Premières planches">
                                @foreach ($ready->take(8) as $page)
                                    <li><img src="{{ $page->thumbnailUrl() }}" alt="{{ $page->alt ?: 'Page '.$loop->iteration }}" loading="lazy" width="90"></li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </section>

                <section class="neo-card neo-card--a2 nd-panel" aria-labelledby="cs-publish-title">
                    <div class="neo-card__head">
                        <h2 id="cs-publish-title" class="neo-card__name">Publication</h2>
                    </div>
                    <div class="nd-panel__body">
                        @if ($problems)
                            <div class="nd-flash nd-flash--warn">
                                <p><strong>Avant de publier :</strong></p>
                                <ul>
                                    @foreach ($problems as $problem)
                                        <li>{{ $problem }}</li>
                                    @endforeach
                                </ul>
                                <p class="nd-row cs-actions">
                                    <a href="{{ route('comics.studio.chapters.pages', [$comic, $chapter]) }}" class="cs-link">Les planches</a>
                                    <a href="{{ route('comics.studio.edit', [$comic, 'chapitre' => $chapter->id]) }}" class="cs-link">La fiche</a>
                                </p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('comics.studio.chapters.publish', [$comic, $chapter]) }}" class="nd-form" x-data="{ action: @js($action) }">
                            @csrf
                            <fieldset class="nd-field">
                                <legend>Quand ?</legend>
                                <div class="pub__choices cs-radios">
                                    <label class="cs-radio">
                                        <input type="radio" name="action" value="{{ ComicChapter::STATUS_PUBLISHED }}" x-model="action" @checked($action === ComicChapter::STATUS_PUBLISHED)>
                                        <span><strong>Publier maintenant</strong></span>
                                    </label>
                                    <label class="cs-radio">
                                        <input type="radio" name="action" value="{{ ComicChapter::STATUS_SCHEDULED }}" x-model="action" @checked($action === ComicChapter::STATUS_SCHEDULED)>
                                        <span><strong>Programmer</strong> : sortie automatique à la date choisie.</span>
                                    </label>
                                    <label class="cs-radio">
                                        <input type="radio" name="action" value="{{ ComicChapter::STATUS_DRAFT }}" x-model="action" @checked($action === ComicChapter::STATUS_DRAFT)>
                                        <span><strong>Enregistrer en brouillon</strong> : visible par toi seul.</span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="nd-field" x-show="action === '{{ ComicChapter::STATUS_SCHEDULED }}'" @if ($action !== ComicChapter::STATUS_SCHEDULED) x-cloak @endif>
                                <label for="cs-date">Date et heure de sortie <span class="nd-hint">({{ ComicStudio::timezoneLabel() }})</span></label>
                                <input id="cs-date" type="datetime-local" name="scheduled_at" class="neo-input" value="{{ $scheduled }}" min="{{ $min }}"
                                    x-bind:required="action === '{{ ComicChapter::STATUS_SCHEDULED }}'"
                                    @error('scheduled_at') aria-invalid="true" aria-describedby="cs-date-error" @enderror>
                                @error('scheduled_at')<p id="cs-date-error" class="nd-error">{{ $message }}</p>@enderror
                            </div>

                            @if ($needsReview)
                                <p class="nd-hint">L’équipe relit chaque {{ $oneShot ? 'nouveau récit' : 'nouveau chapitre' }} avant sa mise en ligne. Un chapitre programmé sort à l’heure prévue s’il a été validé, sinon dès sa validation.</p>
                            @endif
                            @unless ($oneShot)
                                <p class="nd-hint">Les abonnés de la série sont prévenus à la sortie du chapitre.</p>
                            @endunless

                            <button type="submit" class="neo-btn neo-btn--a1 nd-submit"
                                x-text="action === '{{ ComicChapter::STATUS_DRAFT }}' ? 'Enregistrer le brouillon' : (action === '{{ ComicChapter::STATUS_SCHEDULED }}' ? 'Programmer la sortie' : 'Publier maintenant')">Publier maintenant</button>
                        </form>
                    </div>
                </section>
            </div>

            <div class="nd-row cs-actions">
                <a href="{{ route('comics.studio.chapters.pages', [$comic, $chapter]) }}" class="neo-btn neo-btn--ghost">← Les planches</a>
            </div>
        </div>
    </div>
</x-world-layout>
