@php
    /** @var \Illuminate\Support\Collection $animations */
    use App\Models\Animation;
    use App\Support\AnimationStudio;
    $headingTag = $headingTag ?? 'h2';
    $ffmpegAvailable = $ffmpegAvailable ?? \App\Support\Ffmpeg::available();
@endphp

<section id="mes-animations" class="nd-section cs-section as-section" aria-labelledby="animations-titre">
    <div class="nd-section__head">
        <{{ $headingTag }} id="animations-titre" class="nd-h2 font-bebas">Mes animations <span class="only-nuit font-jp">アニメ</span></{{ $headingTag }}>
        @if ($ffmpegAvailable)
            <a href="{{ route('animations.studio.create') }}" class="neo-btn neo-btn--a1">+ Nouvelle animation</a>
        @endif
    </div>

    @unless ($ffmpegAvailable)
        <p class="nd-flash nd-flash--warn as-unavailable" role="status">{{ \App\Support\Ffmpeg::UNAVAILABLE_MESSAGE }} Tes animations déjà en ligne restent visibles.</p>
    @endunless

    <div class="cs-quota">
        <p class="cs-quota__label" id="as-quota-label">
            Stockage des animations : <strong>{{ $storage['usedLabel'] }}</strong> sur {{ $storage['limitLabel'] }}
        </p>
        <span class="nd-meter" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $storage['ratio'] }}"
            aria-labelledby="as-quota-label" aria-valuetext="{{ $storage['ratio'] }} % utilisés">
            <span style="width: {{ $storage['ratio'] }}%"></span>
        </span>
        <p class="nd-hint">Quota séparé de ceux des illustrations et des récits dessinés.</p>
    </div>

    @if ($animations->isEmpty())
        <p class="nd-empty">Aucune animation pour le moment. Une boucle muette (comme un GIF) ou un clip avec son, jusqu’à {{ \App\Support\AnimationMedia::durationLabel(\App\Support\AnimationMedia::maxDuration()) }} : lance-toi avec « Nouvelle animation ».</p>
    @else
        <ul class="cs-list as-list" role="list">
            @foreach ($animations as $animation)
                @php
                    [$statusLabel, $statusTone] = AnimationStudio::statusFor($animation);
                @endphp
                <li class="cs-card as-card">
                    <a href="{{ route('animations.studio.manage', $animation) }}" class="as-card__media">
                        @if ($animation->isReady() && $animation->posterUrl())
                            <img src="{{ $animation->posterUrl() }}" alt="" loading="lazy" width="320" height="180">
                        @else
                            <span class="as-card__state as-card__state--{{ $animation->statut_traitement }}">{{ AnimationStudio::processingLabel($animation) }}</span>
                        @endif
                        @if ($animation->durationLabel())
                            <span class="as-card__time">{{ $animation->durationLabel() }}</span>
                        @endif
                        <span class="sr-only">Gérer « {{ $animation->titre }} »</span>
                    </a>
                    <div class="cs-card__body">
                        <p class="nd-kicker">{{ $animation->modeLabel() }}@if ($animation->genreLabel()) · {{ $animation->genreLabel() }}@endif</p>
                        <h3 class="cs-card__title">{{ $animation->titre }}</h3>
                        <p class="cs-status cs-status--{{ $statusTone }}">{{ $statusLabel }}</p>
                        @if ($animation->statut_traitement === Animation::FAILED)
                            <p class="as-error">Échec de la conversion</p>
                        @endif
                        <p class="nd-work__stats">
                            <span title="Vues">👁 {{ (int) $animation->view_count }}<span class="sr-only"> vues</span></span>
                            <span title="J’aime">♥ {{ $animation->likes_count ?? $animation->likes()->count() }}<span class="sr-only"> J’aime</span></span>
                        </p>
                        <a href="{{ route('animations.studio.manage', $animation) }}" class="neo-btn neo-btn--a2 cs-card__go">
                            Gérer<span class="sr-only"> « {{ $animation->titre }} »</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
