@php
    $artistName = $artist->user?->name ?? 'Artiste';
    $initial = mb_strtoupper(mb_substr(trim($artistName), 0, 1)) ?: 'A';
    $avatarUrl = $artist->user?->google_avatar;
    $number = str_pad((string) $illustration->id, 2, '0', STR_PAD_LEFT);
    $altText = $illustration->title.' par '.$artistName;
    $typeLabel = $illustration->typeLabel();
    $typeTone = match ($illustration->type) {
        'croquis' => 'nb-badge-a2',
        'couleur' => 'nb-badge-a1',
        default => '',
    };
@endphp

<x-world-layout
    world="neo"
    :title="$illustration->title.' – '.$artistName"
    :description="'« '.$illustration->title.' », illustration '.\App\Support\Fr::de($artistName).' sur '.$collectif['nom'].'.'"
    :image="$illustration->thumbnailUrl()"
>
    @unless ($illustration->isVisible())
        <p class="preview-banner" role="status">
            {{ $illustration->statusLabel() }}{{ $illustration->isRejected() && $illustration->moderation_note ? ' ('.$illustration->moderation_note.')' : '' }} : cette page n'est visible que par toi et l'équipe.
            <a href="{{ route('illustrations.edit', $illustration) }}">Modifier</a>
        </p>
    @endunless

    <section class="nb-hero nb-work" aria-labelledby="nb-work-title">
        <div class="nb-hero__deco" aria-hidden="true">
            <div class="scanlines only-nuit"></div>
        </div>

        <div class="w-container nb-work__grid">
            {{-- Œuvre : clic = plein écran --}}
            <div class="nb-work__stage">
                <button type="button" class="nb-art tilt" data-lightbox-open aria-haspopup="dialog" aria-label="Afficher « {{ $illustration->title }} » en plein écran">
                    <img src="{{ $illustration->imageUrl() }}" alt="{{ $altText }}" fetchpriority="high">
                    <span class="nb-art__tag" aria-hidden="true">
                        <span class="only-nuit">作品 #{{ $number }}</span>
                        <span class="only-jour">ŒUVRE N°{{ $number }}</span>
                    </span>
                    <span class="nb-art__zoom" aria-hidden="true">⤢ PLEIN ÉCRAN</span>
                </button>
            </div>

            {{-- Colonne d'informations --}}
            <div>
                <nav class="nb-crumbs" aria-label="Fil d'Ariane">
                    <ol>
                        <li><a href="{{ route('public.illustrations') }}">Galerie</a></li>
                        <li><a href="{{ route('public.artist', $artist) }}">{{ $artistName }}</a></li>
                        <li><span aria-current="page">{{ $illustration->title }}</span></li>
                    </ol>
                </nav>

                @if ($typeLabel)
                    <p class="mt-4">
                        <a href="{{ route('public.illustrations', ['type' => $illustration->type]) }}" class="neo-badge {{ $typeTone }}">{{ $typeLabel }}</a>
                    </p>
                @endif

                <h1 id="nb-work-title" class="nb-title nb-work__title"><span class="glitch">{{ $illustration->title }}</span></h1>

                <a href="{{ route('public.artist', $artist) }}" class="nb-byline">
                    <span class="nb-avatar nb-avatar--sm" aria-hidden="true">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="" referrerpolicy="no-referrer">
                        @else
                            {{ $initial }}
                        @endif
                    </span>
                    <span>
                        <span class="nb-byline__label">Une œuvre {{ \App\Support\Fr::deArticle($artistName) }}</span>
                        <span class="nb-byline__name">{{ $artistName }}</span>
                    </span>
                </a>

                {{-- Description de l'œuvre ; à défaut, la bio de l'artiste. --}}
                @if ($illustration->description)
                    <p class="nb-bio">{!! nl2br(e($illustration->description)) !!}</p>
                @elseif ($artist->bio)
                    <p class="nb-bio">{{ $artist->bio }}</p>
                @endif

                @if ($illustration->tags)
                    <ul class="nb-tags" aria-label="Mots-clés">
                        @foreach ($illustration->tags as $tag)
                            <li><a href="{{ route('search', ['q' => $tag]) }}">#{{ $tag }}</a></li>
                        @endforeach
                    </ul>
                @endif

                <dl class="nb-facts">
                    @if ($typeLabel)
                        <div><dt>Type</dt><dd>{{ $typeLabel }}</dd></div>
                    @endif
                    @if ($illustration->isPublished())
                        <div><dt>Publiée le</dt><dd>{{ $illustration->published_at->locale('fr')->isoFormat('D MMMM YYYY') }}</dd></div>
                    @endif
                </dl>

                @if ($isOwner)
                    <p class="mt-4"><a href="{{ route('illustrations.edit', $illustration) }}" class="neo-btn neo-btn--a2">Modifier l'œuvre</a></p>
                @endif

                <div class="nb-actions">
                    <x-like-button :item="$illustration" />

                    @auth
                        <form method="POST" action="{{ $isFavorite ? route('illustrations.unfavorite', $illustration) : route('illustrations.favorite', $illustration) }}">
                            @csrf
                            @if ($isFavorite)
                                @method('DELETE')
                            @endif
                            <button type="submit" class="neo-btn {{ $isFavorite ? 'neo-btn--outline' : 'neo-btn--a1' }}" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                                <span aria-hidden="true">♥</span> {{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="neo-btn neo-btn--a1">
                            <span aria-hidden="true">♥</span> Se connecter pour ajouter aux favoris
                        </a>
                    @endauth

                    @if ($immersivePortfolio)
                        <a href="{{ route('artist-portfolios.show', $immersivePortfolio) }}" class="neo-btn neo-btn--ghost">
                            Voir en immersif <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </div>

                <x-report-button :work="$illustration" />

                <x-share-buttons class="nb-share" :title="'« '.$illustration->title.' » '.\App\Support\Fr::de($artistName).' sur '.$collectif['nom']" label="Partager l'œuvre" />

                @if ($previousIllustration || $nextIllustration)
                    <nav class="nb-pager" aria-label="Œuvres {{ \App\Support\Fr::de($artistName) }}">
                        @if ($previousIllustration)
                            <a href="{{ route('public.illustration', $previousIllustration) }}" rel="prev">
                                <small>← ŒUVRE PRÉCÉDENTE</small>
                                <b>{{ $previousIllustration->title }}</b>
                            </a>
                        @else
                            <span aria-hidden="true"><small>← ŒUVRE PRÉCÉDENTE</small><b>—</b></span>
                        @endif
                        @if ($nextIllustration)
                            <a href="{{ route('public.illustration', $nextIllustration) }}" rel="next" class="is-next">
                                <small>ŒUVRE SUIVANTE →</small>
                                <b>{{ $nextIllustration->title }}</b>
                            </a>
                        @else
                            <span aria-hidden="true" class="is-next"><small>ŒUVRE SUIVANTE →</small><b>—</b></span>
                        @endif
                    </nav>
                @endif
            </div>
        </div>
    </section>

    {{-- ===== Dans la même rue ===== --}}
    @if ($artistIllustrations->isNotEmpty())
        <section class="nb-section" aria-labelledby="nb-same-title">
            <div class="w-container">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// 同じ通り</span><span class="only-jour">// DU MÊME STUDIO</span></p>
                        <h2 id="nb-same-title" class="nb-h2 mt-2">Dans la même rue</h2>
                    </div>
                    <a href="{{ route('public.artist', $artist) }}" class="neo-btn neo-btn--ghost">Tout le studio <span aria-hidden="true">→</span></a>
                </div>
                <div class="nb-tiles mt-10">
                    @foreach ($artistIllustrations->take(8) as $artistIllustration)
                        <x-public.illustration-card :illustration="$artistIllustration" :show-artist="false" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===== Suggestions (masqué si vide) ===== --}}
    @if ($suggestedIllustrations->isNotEmpty())
        <section class="nb-section {{ $artistIllustrations->isNotEmpty() ? 'pt-0' : '' }}" aria-labelledby="nb-suggest-title">
            <div class="w-container">
                <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// おすすめ</span><span class="only-jour">// AILLEURS EN VILLE</span></p>
                <h2 id="nb-suggest-title" class="nb-h2 mt-2">Suggestions</h2>
                <div class="nb-tiles mt-10">
                    @foreach ($suggestedIllustrations as $suggestion)
                        <x-public.illustration-card :illustration="$suggestion" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===== Lightbox plein écran ===== --}}
    <dialog id="nb-lightbox" class="nb-lightbox" aria-label="{{ $illustration->title }} en plein écran">
        <div class="nb-lightbox__bar">
            <p class="nb-lightbox__title">{{ $illustration->title }}</p>
            <button type="button" class="neo-btn neo-btn--a1" data-lightbox-close>Fermer</button>
        </div>
        <img src="{{ $illustration->imageUrl() }}" alt="{{ $altText }}" loading="lazy">
    </dialog>

    <script>
        (function () {
            var dialog = document.getElementById('nb-lightbox');
            var opener = document.querySelector('[data-lightbox-open]');
            if (!dialog || !opener) return;
            opener.addEventListener('click', function () {
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    window.open(@json($illustration->imageUrl()), '_blank', 'noopener');
                }
            });
            dialog.querySelector('[data-lightbox-close]').addEventListener('click', function () { dialog.close(); });
            // Clic en dehors de l'image = fermer ; Échap est géré nativement par <dialog>.
            dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
            dialog.addEventListener('close', function () { opener.focus(); });
        })();
    </script>
</x-world-layout>
