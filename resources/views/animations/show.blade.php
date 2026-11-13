@php
    use App\Support\Fr;

    $authorName = $animation->user?->name ?? 'Artiste';
    $initial = mb_strtoupper(mb_substr(trim($authorName), 0, 1)) ?: 'A';
    $avatarUrl = $animation->user?->avatarUrl();
    $galleryUrl = route('public.illustrations', ['format' => 'anime']);
    $processingLabel = match ($animation->statut_traitement) {
        \App\Models\Animation::QUEUED => 'En file d’attente de conversion',
        \App\Models\Animation::PROCESSING => 'Conversion en cours',
        \App\Models\Animation::FAILED => 'Échec de la conversion'.($animation->erreur ? ' : '.$animation->erreur : ''),
        default => null,
    };
    $previewStatus = $animation->isPublished() && $animation->isApproved() && ! $animation->isReady()
        ? $processingLabel
        : $animation->statusLabel().($animation->isRejected() && $animation->moderation_note ? ' ('.$animation->moderation_note.')' : '');
    $kindLabel = $animation->isLoop() ? 'Une animation' : 'Un clip';
@endphp

<x-world-layout
    world="neo"
    :title="$animation->titre.' – '.$authorName"
    :description="$animation->description ? \Illuminate\Support\Str::limit($animation->description, 170) : '« '.$animation->titre.' », '.mb_strtolower($kindLabel).' '.Fr::de($authorName).' à voir sur '.$collectif['nom'].'.'"
    :image="$animation->posterUrl()"
    :robots-noindex="$preview"
>
    @if ($preview)
        <p class="preview-banner" role="status">
            {{ $previewStatus }} : cette animation n'est visible que par toi et l'équipe.
            @if ($isOwner && Route::has('animations.studio.index'))
                <a href="{{ route('animations.studio.index') }}">Mes animations</a>
            @endif
        </p>
    @endif

    <section class="nb-hero nb-work an-sheet" aria-labelledby="an-sheet-title">
        <div class="nb-hero__deco" aria-hidden="true">
            <div class="scanlines only-nuit"></div>
        </div>

        <div class="w-container">
            <nav class="nb-crumbs" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ $galleryUrl }}">Galerie animée</a></li>
                    @if ($animation->artist)
                        <li><a href="{{ route('public.artist', $animation->artist) }}">{{ $authorName }}</a></li>
                    @endif
                    <li><span aria-current="page">{{ $animation->titre }}</span></li>
                </ol>
            </nav>

            {{-- ===== Lecteur ===== --}}
            <div id="lecteur" class="an-sheet__player mt-6">
                @if (! $animation->isReady())
                    <div class="an-pending" style="--ap-ratio: {{ $animation->aspectRatio() }};">
                        <p>{{ $processingLabel ?? 'La vidéo n’est pas encore prête.' }}</p>
                    </div>
                @elseif ($warning)
                    <div class="an-warning" role="alertdialog" aria-labelledby="an-warning-title" aria-describedby="an-warning-text" style="--ap-ratio: {{ $animation->aspectRatio() }};">
                        @if ($animation->posterUrl())
                            <img src="{{ $animation->posterUrl() }}" alt="" class="an-warning__poster">
                        @endif
                        <div class="an-warning__box">
                            <p id="an-warning-title" class="an-warning__title">Contenu sensible</p>
                            <p id="an-warning-text">Cette animation contient des images qui peuvent heurter (violence, nudité ou thèmes difficiles).</p>
                            <div class="an-warning__actions">
                                <a href="{{ route('animations.show', [$animation, 'avertissement' => 'lu']) }}#lecteur" class="neo-btn neo-btn--a1">Voir l’animation</a>
                                <a href="{{ $galleryUrl }}" class="neo-btn neo-btn--ghost">Retour à la galerie</a>
                            </div>
                        </div>
                    </div>
                @else
                    <x-animation-player :animation="$animation" :view-url="$animation->isVisible() ? route('animations.view', $animation) : null" />
                @endif
            </div>

            <div class="an-sheet__grid mt-8">
                <div>
                    <p class="an-sheet__badges">
                        <span class="neo-badge nb-badge-a1">Animé · {{ $animation->modeLabel() }}</span>
                        @if ($animation->contenu_sensible)
                            <span class="bd-badge bd-badge--sensitive">Contenu sensible</span>
                        @endif
                    </p>

                    <h1 id="an-sheet-title" class="nb-title nb-work__title"><span class="glitch">{{ $animation->titre }}</span></h1>

                    @if ($animation->artist)
                        <a href="{{ route('public.artist', $animation->artist) }}" class="nb-byline">
                    @else
                        <span class="nb-byline">
                    @endif
                        <span class="nb-avatar nb-avatar--sm" aria-hidden="true">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="" referrerpolicy="no-referrer">
                            @else
                                {{ $initial }}
                            @endif
                        </span>
                        <span>
                            <span class="nb-byline__label">{{ $kindLabel }} {{ Fr::deArticle($authorName) }}</span>
                            <span class="nb-byline__name">{{ $authorName }}</span>
                        </span>
                    @if ($animation->artist)
                        </a>
                    @else
                        </span>
                    @endif

                    @if ($animation->description)
                        <p class="nb-bio">{!! nl2br(e($animation->description)) !!}</p>
                    @endif

                    @if ($animation->tags)
                        <ul class="nb-tags" aria-label="Mots-clés">
                            @foreach ($animation->tags as $tag)
                                <li><a href="{{ route('search', ['q' => $tag]) }}">#{{ $tag }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div>
                    <dl class="nb-facts">
                        @if ($animation->genreLabel())
                            <div><dt>Genre</dt><dd>{{ $animation->genreLabel() }}</dd></div>
                        @endif
                        <div><dt>Format</dt><dd>{{ $animation->isLoop() ? 'Boucle muette' : 'Clip avec son' }}</dd></div>
                        @if ($animation->durationLabel())
                            <div><dt>Durée</dt><dd>{{ $animation->durationLabel() }}</dd></div>
                        @endif
                        @if ($animation->isPublished())
                            <div><dt>Publiée le</dt><dd><time datetime="{{ $animation->published_at->toDateString() }}">{{ $animation->published_at->locale('fr')->isoFormat('D MMMM YYYY') }}</time></dd></div>
                        @endif
                        @if ((int) $animation->view_count > 0)
                            <div><dt>Vues</dt><dd>{{ $animation->view_count }}</dd></div>
                        @endif
                    </dl>

                    @if (session('animation_status'))
                        <p class="bd-flash" role="status">{{ session('animation_status') }}</p>
                    @endif

                    @if ($animation->isVisible())
                        <div class="nb-actions">
                            @unless ($isOwner)
                                @auth
                                    <form method="POST" action="{{ route('animations.favorite', $animation) }}">
                                        @csrf
                                        <button type="submit" class="neo-btn neo-btn--ghost" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                                            <span aria-hidden="true">♥</span> {{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ \App\Support\GoogleAuth::loginUrl() }}" class="neo-btn neo-btn--ghost">Se connecter pour garder en favori</a>
                                @endauth
                            @endunless
                            <x-like-button :item="$animation" />
                        </div>

                        <x-report-button :work="$animation" />
                        <x-share-buttons class="nb-share" :title="'« '.$animation->titre.' » '.Fr::de($authorName).' sur '.$collectif['nom']" label="Partager l’animation" />
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Du même studio ===== --}}
    @if ($sameStudio->isNotEmpty())
        <section class="nb-section" aria-labelledby="an-same-title">
            <div class="w-container">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// 同じスタジオ</span><span class="only-jour">// MÊME ATELIER</span></p>
                        <h2 id="an-same-title" class="nb-h2 mt-2">Du même studio</h2>
                    </div>
                    @if ($animation->artist)
                        <a href="{{ route('public.artist', $animation->artist) }}" class="neo-btn neo-btn--ghost">Tout le studio <span aria-hidden="true">→</span></a>
                    @endif
                </div>
                <div class="nb-tiles an-grid mt-10">
                    @foreach ($sameStudio as $other)
                        @include('animations.partials.card', ['animation' => $other, 'showAuthor' => false])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-world-layout>
