{{-- Couverture d'œuvre « Songe » : forme et couleur selon le genre, emblème en médaillon. --}}
@php
    $bookGenre = $document->knownGenre();
    $bookAuthor = $document->writer?->user?->name;
@endphp
<article class="sg-book sg-tone"style="{{ \App\Support\SongeSky::style($bookGenre) }}">
    <a href="{{ route('documents.read', $document) }}" class="sg-book__link">
        <span class="sg-book__cover sg-shape--{{ $bookGenre ?? 'none' }}">
            <img
                src="{{ $document->coverThumbnailUrl() }}"
                alt="{{ $document->title }}{{ $bookAuthor ? ' par '.$bookAuthor : '' }}"
                loading="lazy"
            >
        </span>
        <span class="sg-book__medal" aria-hidden="true">
            <x-genre-emblem :genre="$bookGenre" class="sg-book__emblem" />
        </span>
        <span class="sg-book__title">{{ $document->title }}</span>
    </a>
    <p class="sg-book__meta">
        @if ($bookAuthor && ($showAuthor ?? true))
            <a href="{{ route('public.writer', $document->writer) }}" class="sg-book__author">{{ $bookAuthor }}</a>
        @endif
        <span class="sg-book__genre">
            {{ $document->genreLabel() ?? 'Genre libre' }} · {{ strtoupper($document->file_type) }}
        </span>
    </p>
</article>
