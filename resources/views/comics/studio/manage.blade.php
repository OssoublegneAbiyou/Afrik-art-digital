@php
    /** @var \App\Models\Comic $comic */
    use App\Support\ComicStudio;
    use App\Support\Fr;

    $oneShot = $comic->isOneShot();
    [$statusLabel, $statusTone] = $status;
    $publicUrl = Route::has('comics.show') && $comic->isVisible() && $comic->chapters()->exists() ? route('comics.show', $comic) : null;
    $first = $chapters->first();
@endphp

<x-world-layout world="neo" :title="($oneShot ? 'Gérer · ' : 'Gérer la série · ').$comic->titre" description="Gestion d’un récit dessiné." robots-noindex>
    <div class="nd cs">
        <section class="nd-hero">
            <div class="scanlines only-nuit" aria-hidden="true"></div>
            <div class="w-container nd-hero__inner cs-manage-hero">
                <div class="nd-hero__text">
                    <p class="neo-badge">{{ $comic->typeLabel() }}@if ($comic->seriesStatusLabel()) · {{ $comic->seriesStatusLabel() }}@endif</p>
                    <h1 class="nd-title font-bebas"><span class="glitch nd-title__name">{{ $comic->titre }}</span></h1>
                    <p class="cs-status cs-status--{{ $statusTone }}">{{ $statusLabel }}</p>
                    @if ($comic->isRejected() && $comic->moderation_note)
                        <p class="nd-hint">Motif de l’équipe : {{ $comic->moderation_note }}</p>
                    @endif
                    <div class="nd-actions">
                        <a href="{{ route('comics.studio.edit', $comic) }}" class="neo-btn neo-btn--a2">Modifier la fiche</a>
                        @if ($oneShot && $first)
                            <a href="{{ route('comics.studio.chapters.pages', [$comic, $first]) }}" class="neo-btn neo-btn--ghost">Planches</a>
                            <a href="{{ route('comics.studio.chapters.publication', [$comic, $first]) }}" class="neo-btn neo-btn--ghost">Publication</a>
                        @else
                            <a href="{{ route('comics.studio.chapters.create', $comic) }}" class="neo-btn neo-btn--a1">+ Ajouter un chapitre</a>
                        @endif
                        @if ($publicUrl)
                            <a href="{{ $publicUrl }}" class="neo-btn neo-btn--ghost">Voir la page publique ↗</a>
                        @endif
                        <a href="{{ route('dashboard') }}#mes-recits-dessines" class="neo-btn neo-btn--ghost">Mon espace artiste</a>
                    </div>
                </div>
                @if ($comic->coverUrl())
                    <img src="{{ $comic->coverUrl() }}" alt="Couverture de « {{ $comic->titre }} »" class="cs-manage-cover" width="200">
                @endif
            </div>
        </section>

        <div class="w-container nd-body">
            @include('comics.studio._flash')

            {{-- Statistiques --}}
            <section class="nd-section cs-first" aria-labelledby="cs-stats-title">
                <h2 id="cs-stats-title" class="nd-h2 font-bebas">Statistiques</h2>
                <dl class="nd-stats cs-stats">
                    <div class="nd-stat nd-stat--a1"><dt>Vues</dt><dd><span class="font-bebas">{{ $stats['views'] }}</span></dd></div>
                    <div class="nd-stat nd-stat--a2"><dt>J’aime</dt><dd><span class="font-bebas">{{ $stats['likes'] }}</span></dd></div>
                    @unless ($oneShot)
                        <div class="nd-stat nd-stat--a3"><dt>Abonnés</dt><dd><span class="font-bebas">{{ $stats['followers'] }}</span></dd></div>
                    @endunless
                    <div class="nd-stat nd-stat--a2">
                        <dt>Lecture complète</dt>
                        <dd><span class="font-bebas">{{ $stats['rate'] === null ? '—' : $stats['rate'].' %' }}</span>
                            <small>{{ Fr::count($stats['readers'], 'lecteur') }}</small></dd>
                    </div>
                </dl>
                <p class="nd-hint">Lecture complète : part des lecteurs connectés arrivés à 90 % au moins {{ $oneShot ? 'du récit' : 'd’un chapitre' }}.</p>
            </section>

            {{-- Chapitres (séries) ou état du one-shot --}}
            <section class="nd-section" aria-labelledby="cs-chapters-title">
                <div class="nd-section__head">
                    <h2 id="cs-chapters-title" class="nd-h2 font-bebas">{{ $oneShot ? 'Planches et publication' : 'Chapitres' }}</h2>
                    @unless ($oneShot)
                        <a href="{{ route('comics.studio.chapters.create', $comic) }}" class="neo-btn neo-btn--a1">+ Ajouter un chapitre</a>
                    @endunless
                </div>

                <div class="nd-table-wrap cs-table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                @unless ($oneShot)<th scope="col">N°</th><th scope="col">Titre</th>@endunless
                                <th scope="col">Statut</th>
                                <th scope="col">Pages</th>
                                <th scope="col">Vues</th>
                                <th scope="col">Lecture complète</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['chapters'] as $row)
                                @php
                                    $chapter = $row['chapter'];
                                    $pagesCount = $chapters->firstWhere('id', $chapter->id)?->pages_count ?? 0;
                                @endphp
                                <tr>
                                    @unless ($oneShot)
                                        <td class="font-bebas cs-table__n">{{ $chapter->numberLabel() }}</td>
                                        <td>{{ $chapter->titre ?: '—' }}</td>
                                    @endunless
                                    <td>
                                        {{ ComicStudio::chapterStatus($chapter) }}
                                        @if ($chapter->isRejected() && $chapter->moderation_note)
                                            <br><small class="nd-hint">Motif : {{ $chapter->moderation_note }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $pagesCount }}</td>
                                    <td>{{ $row['views'] }}</td>
                                    <td>{{ $row['rate'] === null ? '—' : $row['rate'].' % ('.Fr::count($row['readers'], 'lecteur').')' }}</td>
                                    <td class="cs-table__actions">
                                        <a href="{{ route('comics.studio.chapters.pages', [$comic, $chapter]) }}" class="cs-mini">Planches<span class="sr-only">{{ $oneShot ? '' : ' du chapitre '.$chapter->numberLabel() }}</span></a>
                                        <a href="{{ route('comics.studio.chapters.publication', [$comic, $chapter]) }}" class="cs-mini">Publication<span class="sr-only">{{ $oneShot ? '' : ' du chapitre '.$chapter->numberLabel() }}</span></a>
                                        @unless ($oneShot)
                                            <details class="cs-inline">
                                                <summary class="cs-mini">Modifier<span class="sr-only"> le chapitre {{ $chapter->numberLabel() }}</span></summary>
                                                <form method="POST" action="{{ route('comics.studio.chapters.update', [$comic, $chapter]) }}" class="nd-form cs-inline__form">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="nd-field">
                                                        <label for="num-{{ $chapter->id }}">Numéro</label>
                                                        <input id="num-{{ $chapter->id }}" name="numero" type="text" inputmode="decimal" class="neo-input" value="{{ $chapter->numberLabel() }}" required>
                                                    </div>
                                                    <div class="nd-field">
                                                        <label for="tit-{{ $chapter->id }}">Titre</label>
                                                        <input id="tit-{{ $chapter->id }}" name="titre" type="text" class="neo-input" maxlength="120" value="{{ $chapter->titre }}">
                                                    </div>
                                                    <div class="nd-field">
                                                        <label for="note-{{ $chapter->id }}">Mot de l’auteur</label>
                                                        <textarea id="note-{{ $chapter->id }}" name="note_auteur" rows="2" maxlength="500" class="neo-input">{{ $chapter->note_auteur }}</textarea>
                                                    </div>
                                                    <button type="submit" class="neo-btn neo-btn--a2">Enregistrer</button>
                                                </form>
                                            </details>
                                            @if ($chapters->count() > 1)
                                                <form method="POST" action="{{ route('comics.studio.chapters.destroy', [$comic, $chapter]) }}"
                                                    onsubmit="return confirm(@js('Supprimer définitivement le chapitre '.$chapter->numberLabel().' et toutes ses planches ?'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="cs-mini cs-mini--danger">Supprimer<span class="sr-only"> le chapitre {{ $chapter->numberLabel() }}</span></button>
                                                </form>
                                            @endif
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Zone de danger --}}
            <section class="nd-section" aria-labelledby="cs-danger-title">
                <h2 id="cs-danger-title" class="nd-h2 font-bebas">Supprimer {{ $oneShot ? 'le récit' : 'la série' }}</h2>
                <form method="POST" action="{{ route('comics.studio.destroy', $comic) }}" class="cs-danger"
                    onsubmit="return confirm(@js('Supprimer définitivement « '.$comic->titre.' », '.($oneShot ? 'ses planches' : 'tous ses chapitres et leurs planches').' ? Cette action est irréversible.'))">
                    @csrf
                    @method('DELETE')
                    <p class="nd-hint">Supprime définitivement {{ $oneShot ? 'le récit et ses planches' : 'la série, tous ses chapitres et leurs planches' }}, ainsi que ses abonnés et ses J’aime.</p>
                    <button type="submit" class="nd-delete">Supprimer « {{ $comic->titre }} »</button>
                </form>
            </section>
        </div>
    </div>
</x-world-layout>
