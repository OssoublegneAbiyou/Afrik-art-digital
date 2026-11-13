@php
    $socialLinks = \App\Support\SocialLink::collect([
        'Instagram' => ['instagram', $writer->instagram],
        'Facebook' => ['facebook', $writer->facebook],
        'Site web' => ['website', $writer->website],
    ]);
    $firstDocument = $writer->documents->first();
    $sky = \App\Support\SongeSky::get($mainGenre);
    $skyKey = $mainGenre ?: 'all';
    $writerName = $writer->user->name;
@endphp

<x-world-layout
    world="songe"
    :title="$writer->user->name.' — Écrivain'"
    :description="$writer->bio ?: 'Découvrez les œuvres '.\App\Support\Fr::de($writer->user->name).' sur '.$collectif['nom'].'.'"
    :image="$writer->banner_path ? asset('storage/'.$writer->banner_path) : ($firstDocument ? asset('storage/'.$firstDocument->cover_image_path) : null)"
    overlay-header
>
    <x-slot name="head">
        <style>body { --hdr-ink-d: {{ $sky['ink_day'] }}; --hdr-ink-n: {{ $sky['ink_night'] }}; }</style>
    </x-slot>

    {{-- Hero : ciel du genre principal --}}
    <section class="sg-author sg-tone sg-sky sg-author--{{ $skyKey }}" style="{{ \App\Support\SongeSky::style($mainGenre) }}" aria-labelledby="auteur-nom">
        <div class="sg-author__decor w-deco" aria-hidden="true">
            <div class="only-nuit">
                <div class="starfield"></div>
                <span class="star" style="width:4px;height:4px;top:22%;left:38%;"></span>
                <span class="star" style="width:3px;height:3px;top:46%;left:8%;animation-delay:-1.2s;"></span>
                <span class="star" style="width:5px;height:5px;top:30%;left:58%;animation-delay:-2.2s;"></span>
                <span class="shooting-star" style="top:16%;left:20%;"></span>
            </div>

            @if ($skyKey === 'thriller')
                {{-- Éclipse, réverbère et son cône de lumière, loupe, traces de pas --}}
                <span class="sg-eclipse"></span>
                <span class="sg-cone only-nuit"></span>
                <svg class="sg-lamp" viewBox="0 0 160 420" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M40 412 V96 q0-40 44-40 h22"/>
                    <path d="M100 56 h40 l-11 24 h-18 Z"/>
                    <circle cx="120" cy="86" r="4" fill="currentColor" class="sg-lamp__bulb"/>
                    <path d="M22 412 h36 M30 400 h20"/>
                    <path d="M40 160 h12 M40 240 h8"/>
                </svg>
                <svg class="sg-loupe drift" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                    <circle cx="48" cy="48" r="30"/>
                    <path d="M70 70 l34 34"/>
                    <path d="M32 40 q6-12 18-12"/>
                </svg>
                <svg class="sg-steps" viewBox="0 0 520 90" fill="currentColor">
                    @foreach ([[20, 70, -20], [80, 50, 20], [150, 66, -15], [215, 42, 25], [290, 58, -10], [360, 34, 20], [430, 48, -15], [495, 24, 25]] as [$x, $y, $r])
                        <ellipse cx="{{ $x }}" cy="{{ $y }}" rx="7" ry="12" transform="rotate({{ $r + 60 }} {{ $x }} {{ $y }})"/>
                    @endforeach
                </svg>
            @else
                <div class="only-nuit"><span class="crescent sg-author__moon drift"></span></div>
                @if (in_array($skyKey, ['fantasy', 'conte', 'poesie', 'all'], true))
                    <div class="only-jour">
                        <span class="cloud drift" style="width:200px;height:50px;top:24%;right:34%;"></span>
                        <span class="cloud drift" style="width:150px;height:38px;top:60%;right:6%;animation-delay:-4s;"></span>
                    </div>
                @endif
                @if ($skyKey === 'conte')
                    <span class="sg-sun only-jour"></span>
                    <span class="sg-horizon"></span>
                @endif
                @if ($skyKey === 'poesie')
                    @foreach ([[54, 0], [62, -2], [70, -4.5], [78, -1], [86, -6], [93, -3]] as [$left, $delay])
                        <span class="petal sg-author__petal" style="left:{{ $left }}%;animation-delay:{{ $delay }}s;"></span>
                    @endforeach
                @endif
                <x-genre-emblem :genre="$mainGenre" class="sg-author__emblem drift" />
            @endif
        </div>

        <div class="w-container sg-author__inner">
            <p class="sg-author__badges">
                <span class="sg-badge">Auteur</span>
                @if ($mainGenre)
                    <span class="sg-badge">
                        <x-genre-emblem :genre="$mainGenre" class="h-4 w-4" />
                        Ciel : {{ \App\Models\Genre::labelFor($mainGenre) }}
                    </span>
                @endif
            </p>

            <h1 id="auteur-nom" class="sg-author__name">{{ $writerName }}</h1>

            @if (filled($writer->bio))
                <blockquote class="sg-author__bio">« {{ trim($writer->bio) }} »</blockquote>
            @elseif ($isOwner)
                <p class="sg-author__bio sg-author__bio--empty">
                    Ajoute ta bio depuis <a href="{{ route('dashboard') }}">ton espace</a>.
                </p>
            @endif

            <div class="sg-author__actions">
                @foreach ($socialLinks as $link)
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="songe-btn songe-btn--ghost">{{ $link['label'] }} <span aria-hidden="true">↗</span></a>
                @endforeach
                <span class="sg-author__count">{{ \App\Support\Fr::count($writer->documents->count(), 'œuvre') }}</span>
            </div>

            <x-share-buttons class="sg-share" :title="'Les récits '.\App\Support\Fr::de($writerName).' sur '.$collectif['nom']" label="Partager" />
        </div>

        <svg class="sg-wave w-deco" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 64 C 240 120 420 10 720 52 C 1000 92 1180 20 1440 58 V120 H0 Z"/>
        </svg>
    </section>

    @if ($writer->banner_path)
        <div class="w-container sg-author__banner-wrap">
            <img src="{{ asset('storage/'.$writer->banner_path) }}" alt="Bannière {{ \App\Support\Fr::de($writerName) }}" class="sg-author__banner" loading="lazy">
        </div>
    @endif

    {{-- Sa bibliothèque --}}
    <section class="w-container sg-section sg-section--last" aria-labelledby="bibliotheque-titre">
        <header class="sg-section__head sg-section__head--row">
            <div>
                <p class="sg-eyebrow sg-eyebrow--acc">Ses histoires</p>
                <h2 id="bibliotheque-titre" class="sg-section__title">Sa <em>bibliothèque</em></h2>
            </div>
            <p class="sg-count">{{ \App\Support\Fr::count($writer->documents->count(), 'œuvre') }}</p>
        </header>

        @if ($writer->documents->isNotEmpty())
            <div class="sg-library">
                @foreach ($writer->documents as $document)
                    @php
                        $docGenre = $document->knownGenre();
                        $isBookmarkedDoc = in_array($document->id, $bookmarkedDocumentIds ?? [], true);
                    @endphp
                    <article class="songe-card sg-work sg-tone" style="{{ \App\Support\SongeSky::style($docGenre) }}">
                        <a href="{{ route('documents.read', $document) }}" class="sg-work__cover" tabindex="-1" aria-hidden="true">
                            <span class="sg-book__cover sg-shape--{{ $docGenre ?? 'none' }}">
                                <img src="{{ $document->coverThumbnailUrl() }}" alt="" loading="lazy">
                            </span>
                            <span class="sg-book__medal"><x-genre-emblem :genre="$docGenre" class="sg-book__emblem" /></span>
                        </a>
                        <div class="sg-work__body">
                            <p class="sg-work__badges">
                                @if ($docGenre)
                                    <span class="sg-writer__genre"><x-genre-emblem :genre="$docGenre" class="h-4 w-4" /> {{ $document->genreLabel() }}</span>
                                @endif
                                <span class="sg-writer__genre">{{ strtoupper($document->file_type) }}</span>
                            </p>
                            <h3 class="sg-work__title">
                                <a href="{{ route('documents.read', $document) }}">{{ $document->title }}</a>
                            </h3>
                            @if ($document->description)
                                <p class="sg-work__desc">{{ $document->description }}</p>
                            @endif
                            <div class="sg-work__actions">
                                <a href="{{ route('documents.read', $document) }}" class="songe-btn">Commencer la lecture <span aria-hidden="true">→</span></a>
                                @auth
                                    <form method="POST" action="{{ $isBookmarkedDoc ? route('documents.unbookmark', $document) : route('documents.bookmark', $document) }}">
                                        @csrf
                                        @if ($isBookmarkedDoc)
                                            @method('DELETE')
                                        @endif
                                        <button type="submit" class="songe-btn songe-btn--ghost">
                                            {{ $isBookmarkedDoc ? 'Retirer le marque-page' : 'Ajouter un marque-page' }}
                                        </button>
                                    </form>
                                @endauth
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="songe-card sg-empty">Cet auteur n'a pas encore publié d'œuvre.</p>
        @endif

        <p class="sg-section__more">
            <a href="{{ route('public.writers') }}" class="songe-btn songe-btn--ghost"><span aria-hidden="true">←</span> Toutes les plumes</a>
        </p>
    </section>
</x-world-layout>
