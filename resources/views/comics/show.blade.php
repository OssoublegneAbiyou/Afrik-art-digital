@php
    use App\Support\Fr;

    $authorName = $comic->user?->name ?? 'Artiste';
    $initial = mb_strtoupper(mb_substr(trim($authorName), 0, 1)) ?: 'A';
    $avatarUrl = $comic->user?->avatarUrl();
    $first = $chapters->first();
    $startUrl = $first ? route('comics.read', [$comic, $first->routeNumber()]) : null;
    $resumeUrl = $resume ? route('comics.read', [$comic, $resume['chapter']->routeNumber(), 'page' => $resume['page']]) : null;
    $statusLabel = $comic->seriesStatusLabel();
    // Données pour la reprise des visiteurs (localStorage) : numéro de route et libellé de chaque chapitre.
    $chapterList = $chapters->map(fn ($c) => ['id' => (string) $c->id, 'n' => $c->routeNumber(), 'label' => $c->numberLabel()])->values();
@endphp

<x-world-layout
    world="neo"
    :title="$comic->titre.' – '.$authorName"
    :description="$comic->resume ? \Illuminate\Support\Str::limit($comic->resume, 170) : '« '.$comic->titre.' », '.mb_strtolower($comic->typeLabel()).' '.Fr::de($authorName).' à lire sur '.$collectif['nom'].'.'"
    :image="$comic->coverUrl()"
>
    @if ($preview && ! $comic->isVisible())
        <p class="preview-banner" role="status">
            {{ $comic->statusLabel() }}{{ $comic->isRejected() && $comic->moderation_note ? ' ('.$comic->moderation_note.')' : '' }} : cette fiche n'est visible que par toi et l'équipe.
            @if (Route::has('comics.studio.manage') && $isOwner)
                <a href="{{ route('comics.studio.manage', $comic) }}">Gérer</a>
            @endif
        </p>
    @endif

    <section class="nb-hero nb-work bd-sheet" aria-labelledby="bd-sheet-title">
        <div class="nb-hero__deco" aria-hidden="true">
            <div class="scanlines only-nuit"></div>
        </div>

        <div class="w-container bd-sheet__grid">
            <div class="bd-sheet__cover">
                @if ($comic->coverUrl())
                    <img src="{{ $comic->coverUrl() }}" alt="Couverture de « {{ $comic->titre }} »" width="600" height="900" fetchpriority="high">
                @else
                    <span class="bd-card__placeholder" aria-hidden="true">漫画</span>
                @endif
            </div>

            <div>
                <nav class="nb-crumbs" aria-label="Fil d'Ariane">
                    <ol>
                        <li><a href="{{ route('comics.index') }}">Récits dessinés</a></li>
                        @if ($comic->artist)
                            <li><a href="{{ route('public.artist', $comic->artist) }}">{{ $authorName }}</a></li>
                        @endif
                        <li><span aria-current="page">{{ $comic->titre }}</span></li>
                    </ol>
                </nav>

                <p class="mt-4 bd-sheet__badges">
                    <span class="neo-badge nb-badge-a1">{{ $comic->typeLabel() }} · {{ $comic->readingKind() }}</span>
                    @if ($comic->contenu_sensible)
                        <span class="bd-badge bd-badge--sensitive">Contenu sensible</span>
                    @endif
                    @if ($comic->hasNewChapter())
                        <span class="bd-badge bd-badge--new">{{ $comic->isOneShot() ? 'NOUVEAU' : 'NOUVEAU CHAPITRE' }}</span>
                    @endif
                </p>

                <h1 id="bd-sheet-title" class="nb-title nb-work__title"><span class="glitch">{{ $comic->titre }}</span></h1>

                @if ($comic->artist)
                    <a href="{{ route('public.artist', $comic->artist) }}" class="nb-byline">
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
                        <span class="nb-byline__label">Un récit {{ Fr::deArticle($authorName) }}</span>
                        <span class="nb-byline__name">{{ $authorName }}</span>
                    </span>
                @if ($comic->artist)
                    </a>
                @else
                    </span>
                @endif

                @if ($comic->resume)
                    <p class="nb-bio">{!! nl2br(e($comic->resume)) !!}</p>
                @endif

                @if ($comic->tags)
                    <ul class="nb-tags" aria-label="Mots-clés">
                        @foreach ($comic->tags as $tag)
                            <li><a href="{{ route('search', ['q' => $tag]) }}">#{{ $tag }}</a></li>
                        @endforeach
                    </ul>
                @endif

                <dl class="nb-facts">
                    @if ($comic->genreLabel())
                        <div><dt>Genre</dt><dd>{{ $comic->genreLabel() }}</dd></div>
                    @endif
                    @if ($statusLabel)
                        <div><dt>Statut</dt><dd>{{ $statusLabel }}</dd></div>
                    @endif
                    @unless ($comic->isOneShot())
                        <div><dt>Chapitres</dt><dd>{{ $chapters->count() }}</dd></div>
                    @endunless
                    <div><dt>Lecture</dt><dd>{{ $comic->format_lecture === 'pages' ? 'Page par page' : 'Défilement vertical' }}{{ $comic->isRightToLeft() ? ', de droite à gauche' : '' }}</dd></div>
                    @if ($followersCount > 0)
                        <div><dt>Abonnés</dt><dd>{{ $followersCount }}</dd></div>
                    @endif
                </dl>

                @if (session('comic_status'))
                    <p class="bd-flash" role="status">{{ session('comic_status') }}</p>
                @endif

                <div class="nb-actions">
                    @if ($startUrl)
                        <a href="{{ $resumeUrl ?? $startUrl }}" class="neo-btn neo-btn--a1 bd-start"
                            data-bd-resume data-comic="{{ $comic->id }}" data-start-url="{{ $startUrl }}"
                            data-chapters="{{ $chapterList->toJson() }}" data-one-shot="{{ $comic->isOneShot() ? '1' : '0' }}"
                            data-base-url="{{ route('comics.read', [$comic, '__N__']) }}" data-has-server="{{ $resume ? '1' : '0' }}">
                            <span data-bd-resume-label>{{ $resume['label'] ?? 'Commencer la lecture' }}</span> <span aria-hidden="true">→</span>
                        </a>
                    @endif

                    @unless ($isOwner)
                        @auth
                            <form method="POST" action="{{ route('comics.follow', $comic) }}">
                                @csrf
                                <button type="submit" class="neo-btn {{ $isFollowing ? 'neo-btn--outline' : 'neo-btn--a2' }}" aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
                                    {{ $isFollowing ? 'Ne plus suivre' : ($comic->isOneShot() ? 'Suivre' : 'Suivre la série') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('comics.favorite', $comic) }}">
                                @csrf
                                <button type="submit" class="neo-btn neo-btn--ghost" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                                    <span aria-hidden="true">♥</span> {{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
                                </button>
                            </form>
                        @else
                            <a href="{{ \App\Support\GoogleAuth::loginUrl() }}" class="neo-btn neo-btn--a2">Se connecter pour suivre</a>
                        @endauth
                    @endunless

                    @if ($comic->isVisible())
                        <x-like-button :item="$comic" />
                    @endif
                </div>

                @if ($comic->isVisible())
                    <x-report-button :work="$comic" />
                    <x-share-buttons class="nb-share" :title="'« '.$comic->titre.' » '.Fr::de($authorName).' sur '.$collectif['nom']" label="Partager le récit" />
                @endif
            </div>
        </div>
    </section>

    {{-- ===== Chapitres ===== --}}
    @if (! $comic->isOneShot() || $preview)
        <section class="nb-section" aria-labelledby="bd-chapters-title">
            <div class="w-container">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// 目次</span><span class="only-jour">// SOMMAIRE</span></p>
                        <h2 id="bd-chapters-title" class="nb-h2 mt-2">Chapitres</h2>
                    </div>
                    <span class="nb-count">{{ Fr::count($chapters->count(), 'chapitre') }}</span>
                </div>

                @if ($chapters->isEmpty())
                    <p class="nb-empty mt-6">Aucun chapitre pour l'instant.</p>
                @else
                    <ol class="bd-chapters mt-8" role="list">
                        @foreach ($chapters as $chapter)
                            @php
                                $read = $progress->get($chapter->id);
                                $state = $read ? ($read->isComplete() ? 'lu' : 'commence') : null;
                            @endphp
                            <li class="bd-chapter {{ $state === 'lu' ? 'is-read' : '' }}" data-chapter-id="{{ $chapter->id }}">
                                <a href="{{ route('comics.read', [$comic, $chapter->routeNumber()]) }}" class="bd-chapter__link">
                                    <span class="bd-chapter__num">{{ $comic->isOneShot() ? 'One-shot' : 'Ch. '.$chapter->numberLabel() }}</span>
                                    <span class="bd-chapter__title">{{ $chapter->titre ?: ($comic->isOneShot() ? $comic->titre : 'Chapitre '.$chapter->numberLabel()) }}</span>
                                    <span class="bd-chapter__meta">
                                        @if ($chapter->isPublished())
                                            <time datetime="{{ $chapter->published_at->toDateString() }}">{{ $chapter->published_at->locale('fr')->isoFormat('D MMM YYYY') }}</time> ·
                                        @endif
                                        {{ Fr::count((int) $chapter->ready_pages_count, 'page') }}
                                    </span>
                                    <span class="bd-chapter__state" data-bd-state>
                                        @if ($state === 'lu')
                                            <span aria-hidden="true">✓</span> Lu
                                        @elseif ($state === 'commence')
                                            Lu à {{ (int) $read->pourcentage }} %
                                        @endif
                                    </span>
                                    @if ($preview && ! $chapter->isVisible())
                                        <span class="bd-badge bd-badge--draft">{{ $chapter->statusLabel() }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </section>
    @endif

    {{-- ===== Du même studio ===== --}}
    @if ($sameStudio->isNotEmpty())
        <section class="nb-section {{ ! $comic->isOneShot() || $preview ? 'pt-0' : '' }}" aria-labelledby="bd-same-title">
            <div class="w-container">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="nb-kicker" aria-hidden="true"><span class="only-nuit">// 同じスタジオ</span><span class="only-jour">// MÊME ATELIER</span></p>
                        <h2 id="bd-same-title" class="nb-h2 mt-2">Du même studio</h2>
                    </div>
                    @if ($comic->artist)
                        <a href="{{ route('public.artist', $comic->artist) }}" class="neo-btn neo-btn--ghost">Tout le studio <span aria-hidden="true">→</span></a>
                    @endif
                </div>
                <div class="nb-tiles bd-grid mt-10">
                    @foreach ($sameStudio as $other)
                        @include('comics.partials.card', ['comic' => $other, 'showAuthor' => false])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Reprise de lecture des visiteurs non connectés (localStorage) et états « Lu » des chapitres. --}}
    @guest
        <script>
            (function () {
                var btn = document.querySelector('[data-bd-resume]');
                if (!btn) return;
                var data = null;
                try { data = JSON.parse(localStorage.getItem('aad-bd-' + btn.dataset.comic) || 'null'); } catch (e) {}
                if (!data || !data.chapitres) return;
                var list = JSON.parse(btn.dataset.chapters || '[]');
                var ids = list.map(function (c) { return c.id; });
                var chapters = {};
                list.forEach(function (c) { chapters[c.id] = c; });
                Object.keys(data.chapitres).forEach(function (id) {
                    var row = document.querySelector('[data-chapter-id="' + id + '"] [data-bd-state]');
                    var pct = data.chapitres[id].pourcentage || 0;
                    if (!row || pct <= 0) return;
                    row.textContent = pct >= 90 ? '✓ Lu' : 'Lu à ' + pct + ' %';
                });
                var last = data.dernier && chapters[String(data.dernier)] ? String(data.dernier) : null;
                if (!last) return;
                var info = data.chapitres[last] || {};
                var page = Math.max(1, parseInt(info.page, 10) || 1);
                var target = last, label;
                if ((info.pourcentage || 0) >= 90) {
                    var next = ids[ids.indexOf(last) + 1];
                    if (next) {
                        target = next; page = 1; label = 'Continuer : ch. ' + chapters[next].label;
                    } else {
                        target = ids[0]; page = 1; label = 'Relire';
                    }
                } else {
                    label = btn.dataset.oneShot === '1' ? 'Reprendre p. ' + page : 'Reprendre ch. ' + chapters[last].label + ', p. ' + page;
                }
                btn.href = btn.dataset.baseUrl.replace('__N__', chapters[target].n) + '?page=' + page;
                btn.querySelector('[data-bd-resume-label]').textContent = label;
            })();
        </script>
    @endguest
</x-world-layout>
