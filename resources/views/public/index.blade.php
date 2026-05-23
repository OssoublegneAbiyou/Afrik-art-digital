@php
    $artistsData = $artists->map(function ($artist) {
        return [
            'id' => $artist->id,
            'name' => $artist->user?->name ?? 'Artiste',
            'bio' => $artist->bio,
            'profileUrl' => route('public.artist', $artist),
            'illustrations' => $artist->illustrations
                ->take(4)
                ->map(function ($illustration) {
                    return [
                        'id' => $illustration->id,
                        'title' => $illustration->title,
                        'imageUrl' => asset('storage/' . $illustration->image_path),
                    ];
                })
                ->values(),
        ];
    })->values();

    $writersData = $writers->map(function ($writer) {
        $firstDocument = $writer->documents->first();

        return [
            'id' => $writer->id,
            'name' => $writer->user?->name ?? 'Écrivain',
            'bio' => $writer->bio,
            'profileUrl' => route('public.writer', $writer),
            'documentsCount' => $writer->documents->count(),
            'highlight' => $firstDocument ? [
                'title' => $firstDocument->title,
                'coverImageUrl' => asset('storage/' . $firstDocument->cover_image_path),
                'fileType' => strtoupper($firstDocument->file_type),
            ] : null,
        ];
    })->values();

    $homeProps = [
        'artists' => $artistsData,
        'writers' => $writersData,
        'totalArtists' => $artists->count(),
        'totalWriters' => $writers->count(),
        'artistsIndexUrl' => route('public.artists'),
        'writersIndexUrl' => route('public.writers'),
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
