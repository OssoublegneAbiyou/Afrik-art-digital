@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-4">{{ $artist->user->name }}</h1>

    @if($artist->bio)
        <p class="mb-4">{{ $artist->bio }}</p>
    @endif

    {{-- Réseaux sociaux --}}
    <div class="mb-6">
        @if($artist->instagram)<a href="{{ $artist->instagram }}" target="_blank" class="text-blue-500 hover:underline mr-2">Instagram</a>@endif
        @if($artist->twitter)<a href="{{ $artist->twitter }}" target="_blank" class="text-blue-500 hover:underline mr-2">Twitter</a>@endif
        @if($artist->behance)<a href="{{ $artist->behance }}" target="_blank" class="text-blue-500 hover:underline mr-2">Behance</a>@endif
        @if($artist->website)<a href="{{ $artist->website }}" target="_blank" class="text-blue-500 hover:underline">Site web</a>@endif
    </div>

    {{-- Galerie complète --}}
    @if($artist->illustrations->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($artist->illustrations as $illustration)
                <div class="border p-2 rounded bg-white shadow">
                    <img src="{{ asset('storage/' . $illustration->image_path) }}" alt="{{ $illustration->title }}" class="mb-2 aspect-square w-full rounded bg-[#f5f5f5] object-contain">
                    <p class="font-semibold">{{ $illustration->title }}</p>
                </div>
            @endforeach
        </div>
    @else
        <p>Cet artiste n’a pas encore d’illustrations.</p>
    @endif

</div>
@endsection
