@php
    /** @var \App\Models\ComicChapter $chapter */
    $comic = $chapter->comic;
    $pages = $chapter->pages;
    $readyPages = $pages->filter->isReady();
@endphp
<li class="adc-item cs-adm-item" id="oeuvre-{{ $key }}">
    <div class="adc-item__media">
        @if ($comic?->coverUrl())
            <img src="{{ $comic->coverUrl() }}" alt="Couverture de « {{ $comic->titre }} »" class="adc-item__img" loading="lazy">
        @endif
    </div>
    <div class="adc-item__body">
        <p class="adc-kicker">{{ \App\Support\Moderation::kindLabel($chapter) }}</p>
        <h3 class="adc-item__title">{{ $comic?->titre }}@if ($comic && ! $comic->isOneShot() && $chapter->titre) — {{ $chapter->titre }}@endif</h3>
        <p class="adm-muted">
            par {{ $author?->name ?? 'Auteur inconnu' }}
            · soumis le <time datetime="{{ $chapter->updated_at->toIso8601String() }}">{{ $chapter->updated_at->locale('fr')->translatedFormat('j F Y à H\hi') }}</time>
            · {{ \App\Support\Fr::count($pages->count(), 'page') }}
        </p>
        @if ($chapter->isScheduled())
            <p class="adm-muted">Sortie programmée le {{ $chapter->published_at->locale('fr')->translatedFormat('j F Y à H\hi') }}</p>
        @endif
        @if ($comic?->isPendingReview())
            <p class="adm-muted"><span class="adm-tag adm-tag--new">Nouveau récit</span> La fiche sera validée avec ce chapitre.</p>
        @endif
        @if ($comic?->contenu_sensible)
            <p class="adm-muted"><span class="adm-tag adm-tag--pending">Contenu sensible</span> signalé par l’auteur.</p>
        @endif
        @if ($comic?->resume)
            <p class="adc-item__text">{{ \Illuminate\Support\Str::limit($comic->resume, 280) }}</p>
        @endif
        @if ($comic?->tags)
            <p class="adm-muted">#{{ implode(' #', $comic->tags) }}</p>
        @endif
        <p class="adm-muted">
            {{ $chapter->rights_certified_at || $comic?->rights_certified_at ? 'Droits certifiés par l’auteur le '.($chapter->rights_certified_at ?? $comic->rights_certified_at)->locale('fr')->translatedFormat('j F Y') : 'Droits non certifiés' }}
            @if ($url !== '#')
                · <a href="{{ $url }}" target="_blank" rel="noopener" class="adm-link">Ouvrir dans le lecteur<span class="sr-only"> (nouvel onglet)</span> ↗</a>
            @endif
        </p>

        @if ($readyPages->isEmpty())
            <p class="adm-empty">Aucune planche prête à afficher.</p>
        @else
            <details class="cs-adm-pages" @if ($readyPages->count() <= 12) open @endif>
                <summary class="adm-link">Planches ({{ $readyPages->count() }})</summary>
                <ol class="cs-adm-pages__grid" role="list">
                    @foreach ($readyPages as $page)
                        <li>
                            <a href="{{ $page->isAnimated() ? $page->videoUrl() : $page->readingUrl() }}" target="_blank" rel="noopener">
                                <img src="{{ $page->thumbnailUrl() }}" alt="{{ $page->alt ?: 'Page '.$loop->iteration }} (agrandir dans un nouvel onglet)" loading="lazy" width="150">
                            </a>
                            <span class="adm-muted">{{ $loop->iteration.($page->tranches ? ' · webtoon' : '').($page->isAnimated() ? ' · animée' : '') }}</span>
                        </li>
                    @endforeach
                </ol>
            </details>
        @endif
    </div>
    <div class="adc-item__actions">
        <form method="POST" action="{{ route('admin.moderation.approve', ['comic_chapter', $chapter->id]) }}">
            @csrf
            <button type="submit" class="adm-btn adc-full">Publier</button>
        </form>
        <details class="adc-details" @if ($hasError) open @endif>
            <summary class="adm-btn adm-btn--danger adc-full">Refuser…</summary>
            <form method="POST" action="{{ route('admin.moderation.reject', ['comic_chapter', $chapter->id]) }}" class="adc-details__form">
                @csrf
                <input type="hidden" name="work" value="{{ $key }}">
                <label for="note-{{ $key }}" class="adm-label">Motif <span class="adm-muted">(envoyé à l’auteur)</span></label>
                <textarea id="note-{{ $key }}" name="note" rows="3" required maxlength="500" class="adm-textarea"
                    placeholder="Ex. : plusieurs planches reprennent des personnages protégés."
                    @if ($hasError) aria-invalid="true" aria-describedby="note-{{ $key }}-error" @endif>{{ $hasError ? old('note') : '' }}</textarea>
                @if ($hasError)
                    <p id="note-{{ $key }}-error" class="adm-error">{{ $errors->first('note') }}</p>
                @endif
                <button type="submit" class="adm-btn adm-btn--danger">Confirmer le refus</button>
            </form>
        </details>
    </div>
</li>
