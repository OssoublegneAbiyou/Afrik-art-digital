@php
    /** @var \Illuminate\Support\Collection $comics */
    use App\Support\ComicStudio;
    use App\Support\Fr;
    $headingTag = $headingTag ?? 'h2';
@endphp

<section id="mes-recits-dessines" class="nd-section cs-section" aria-labelledby="recits-titre">
    <div class="nd-section__head">
        <{{ $headingTag }} id="recits-titre" class="nd-h2 font-bebas">Mes récits dessinés <span class="only-nuit font-jp">漫画</span></{{ $headingTag }}>
        <a href="{{ route('comics.studio.create') }}" class="neo-btn neo-btn--a1">+ Nouveau récit dessiné</a>
    </div>

    <div class="cs-quota">
        <p class="cs-quota__label" id="cs-quota-label">
            Stockage des récits dessinés : <strong>{{ $storage['usedLabel'] }}</strong> sur {{ $storage['limitLabel'] }}
        </p>
        <span class="nd-meter" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $storage['ratio'] }}"
            aria-labelledby="cs-quota-label" aria-valuetext="{{ $storage['ratio'] }} % utilisés">
            <span style="width: {{ $storage['ratio'] }}%"></span>
        </span>
        <p class="nd-hint">Quota séparé de celui des illustrations.</p>
    </div>

    @if ($comics->isEmpty())
        <p class="nd-empty">Aucun récit dessiné pour le moment. One-shot ou série en chapitres (manga, BD, webtoon) : lance-toi avec « Nouveau récit dessiné ».</p>
    @else
        <ul class="cs-list" role="list">
            @foreach ($comics as $comic)
                @php
                    [$statusLabel, $statusTone] = ComicStudio::statusFor($comic);
                    $chapterCount = $comic->allChapters->count();
                    $views = (int) $comic->allChapters->sum('view_count');
                @endphp
                <li class="cs-card">
                    <a href="{{ route('comics.studio.manage', $comic) }}" class="cs-card__cover">
                        @if ($comic->coverUrl())
                            <img src="{{ $comic->coverUrl() }}" alt="" loading="lazy" width="200" height="300">
                        @else
                            <span class="cs-card__nocover" aria-hidden="true">漫</span>
                        @endif
                        <span class="sr-only">Gérer « {{ $comic->titre }} »</span>
                    </a>
                    <div class="cs-card__body">
                        <p class="nd-kicker">{{ $comic->typeLabel() }}@unless ($comic->isOneShot()) · {{ Fr::count($chapterCount, 'chapitre') }}@endunless</p>
                        <h3 class="cs-card__title">{{ $comic->titre }}</h3>
                        <p class="cs-status cs-status--{{ $statusTone }}">{{ $statusLabel }}</p>
                        <p class="nd-work__stats">
                            <span title="Vues">👁 {{ $views }}<span class="sr-only"> vues</span></span>
                            <span title="J’aime">♥ {{ $comic->likes_count }}<span class="sr-only"> J’aime</span></span>
                            @unless ($comic->isOneShot())
                                <span title="Abonnés">★ {{ $comic->followers_count }}<span class="sr-only"> abonnés</span></span>
                            @endunless
                        </p>
                        <a href="{{ route('comics.studio.manage', $comic) }}" class="neo-btn neo-btn--a2 cs-card__go">
                            {{ $comic->isOneShot() ? 'Gérer' : 'Gérer la série' }}<span class="sr-only"> « {{ $comic->titre }} »</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
