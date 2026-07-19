@php
    $illustrationsData = $dailyIllustrations->map(function ($illustration) {
        $artist = $illustration->artist;

        return [
            'id' => $illustration->id,
            'title' => $illustration->title,
            'imageUrl' => asset('storage/' . $illustration->image_path),
            'detailUrl' => route('public.illustration', $illustration),
            'artistName' => $artist?->user?->name ?? 'Artiste',
            'artistBio' => $artist?->bio,
            'artistProfileUrl' => $artist ? route('public.artist', $artist) : '#',
        ];
    })->values();

    $documentsData = $dailyDocuments->map(function ($document) {
        $writer = $document->writer;

        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description,
            'coverImageUrl' => asset('storage/' . $document->cover_image_path),
            'fileType' => strtoupper($document->file_type),
            'readUrl' => route('documents.read', $document),
            'writerName' => $writer?->user?->name ?? 'Écrivain',
            'writerBio' => $writer?->bio,
            'writerProfileUrl' => $writer ? route('public.writer', $writer) : '#',
        ];
    })->values();

    $homeProps = [
        'illustrations' => $illustrationsData,
        'documents' => $documentsData,
        'totalArtists' => $artists->count(),
        'totalWriters' => $writers->count(),
        'totalIllustrations' => $artists->sum(fn ($artist) => $artist->illustrations->count()),
        'totalDocuments' => $writers->sum(fn ($writer) => $writer->documents->count()),
        'artistsIndexUrl' => route('public.artists'),
        'writersIndexUrl' => route('public.writers'),
        'illustrationsIndexUrl' => route('public.illustrations'),
        'literaryWorksIndexUrl' => route('public.literary-works'),
        'featuredArtist' => $featuredArtist ? [
            'name' => $featuredArtist->user?->name ?? 'Artiste',
            'bio' => $featuredArtist->bio,
            'profileUrl' => route('public.artist', $featuredArtist),
            'illustrationsCount' => $featuredArtist->illustrations->count(),
            'highlightImageUrl' => optional($featuredArtist->illustrations->first(), function ($illustration) {
                return asset('storage/' . $illustration->image_path);
            }),
        ] : null,
        'featuredWriter' => $featuredWriter ? [
            'name' => $featuredWriter->user?->name ?? 'Écrivain',
            'bio' => $featuredWriter->bio,
            'profileUrl' => route('public.writer', $featuredWriter),
            'documentsCount' => $featuredWriter->documents->count(),
            'highlight' => ($featuredDocument = $featuredWriter->documents->first()) ? [
                'title' => $featuredDocument->title,
                'coverImageUrl' => asset('storage/' . $featuredDocument->cover_image_path),
                'fileType' => strtoupper($featuredDocument->file_type),
            ] : null,
        ] : null,
    ];
@endphp

<x-app-layout>
    <div
        id="home-root"
        data-props='@json($homeProps)'
    ></div>
</x-app-layout>
