<?php

namespace App\Http\Controllers;

use App\Models\ArtistPortfolio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArtistPortfolioController extends Controller
{
    private array $themes = [
        'pulse',
        'lagoon',
        'sun',
        'night',
        'earth',
        'gallery-white',
        'savanna',
        'indigo',
        'rose',
        'graphite',
    ];

    private array $music = [
        'abidjan',
        'lagos',
        'savane',
        'douala',
        'peaceful',
        'pulse-abel',
        'tranholm',
        'aurora',
        'silence',
        'forest',
    ];

    public function create(Request $request): View
    {
        $artist = $this->artistFor($request);

        return view('artist-portfolios.form', [
            'artist' => $artist,
            'portfolio' => null,
            'illustrations' => $artist->illustrations()->latest()->get(),
            'themes' => $this->themes,
            'music' => $this->music,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $artist = $this->artistFor($request);
        $data = $this->validatePortfolio($request, $artist->id);

        $portfolio = $artist->portfolios()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncItems($request, $portfolio, $data['items']);

        return redirect()
            ->route('artist-portfolios.edit', $portfolio)
            ->with('success', 'Portfolio cree. Vous pouvez le voir en immersion ou continuer a le modifier.');
    }

    public function edit(Request $request, ArtistPortfolio $portfolio): View
    {
        $artist = $this->artistFor($request);
        $this->authorizePortfolio($portfolio, $artist->id);

        return view('artist-portfolios.form', [
            'artist' => $artist,
            'portfolio' => $portfolio->load('items.illustration'),
            'illustrations' => $artist->illustrations()->latest()->get(),
            'themes' => $this->themes,
            'music' => $this->music,
        ]);
    }

    public function update(Request $request, ArtistPortfolio $portfolio): RedirectResponse
    {
        $artist = $this->artistFor($request);
        $this->authorizePortfolio($portfolio, $artist->id);
        $data = $this->validatePortfolio($request, $artist->id);

        $portfolio->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncItems($request, $portfolio, $data['items']);

        return back()->with('success', 'Portfolio mis a jour.');
    }

    public function show(ArtistPortfolio $portfolio): View
    {
        $portfolio->load(['artist.user', 'artist.followers', 'items.illustration']);
        $artist = $portfolio->artist;
        $currentUser = auth()->user();
        $favoriteIllustrationIds = $currentUser
            ? $currentUser->favoriteIllustrations()
                ->whereIn('illustration_id', $portfolio->items->pluck('illustration_id'))
                ->pluck('illustration_id')
                ->all()
            : [];

        $illustrationsData = $portfolio->items->map(function ($item) use ($favoriteIllustrationIds) {
            $illustration = $item->illustration;
            $isFavorite = in_array($illustration->id, $favoriteIllustrationIds, true);

            return [
                'id' => $illustration->id,
                'title' => $illustration->title,
                'imageUrl' => asset('storage/'.$illustration->image_path),
                'meta' => $item->description ?: 'Illustration immersive',
                'theme' => $item->theme,
                'music' => $item->music,
                'customMusicUrl' => $item->custom_music_path ? asset('storage/'.$item->custom_music_path) : null,
                'guideAudioUrl' => $item->guide_audio_path ? asset('storage/'.$item->guide_audio_path) : null,
                'isFavorite' => $isFavorite,
                'favoriteAction' => $isFavorite
                    ? route('illustrations.unfavorite', $illustration)
                    : route('illustrations.favorite', $illustration),
            ];
        })->values();

        $artistProps = [
            'portfolio' => [
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'description' => $portfolio->description,
            ],
            'artist' => [
                'id' => $artist->id,
                'name' => $artist->user?->name ?? 'Artiste',
                'bio' => $artist->bio,
                'bannerUrl' => $artist->banner_path ? asset('storage/'.$artist->banner_path) : null,
            ],
            'illustrations' => $illustrationsData,
            'csrfToken' => csrf_token(),
            'canEdit' => auth()->id() === $artist->user_id,
            'editUrl' => auth()->id() === $artist->user_id ? route('artist-portfolios.edit', $portfolio) : null,
            'isFollowing' => $currentUser
                ? $currentUser->followedArtists()->where('artist_id', $artist->id)->exists()
                : false,
            'follow' => auth()->check() ? [
                'action' => $currentUser?->followedArtists()->where('artist_id', $artist->id)->exists()
                    ? route('artists.unfollow', $artist)
                    : route('artists.follow', $artist),
            ] : null,
            'loginUrl' => route('login'),
            'socialLinks' => [],
        ];

        return view('artist-portfolios.show', compact('artistProps'));
    }

    private function artistFor(Request $request)
    {
        abort_unless($request->user()?->isArtist(), 403);

        return $request->user()->artist()->firstOrCreate([]);
    }

    private function authorizePortfolio(ArtistPortfolio $portfolio, int $artistId): void
    {
        abort_unless($portfolio->artist_id === $artistId, 403);
    }

    private function validatePortfolio(Request $request, int $artistId): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.illustration_id' => [
                'required',
                'integer',
                'exists:illustrations,id',
                function ($attribute, $value, $fail) use ($artistId) {
                    if (! \App\Models\Illustration::where('id', $value)->where('artist_id', $artistId)->exists()) {
                        $fail('Cette illustration ne vous appartient pas.');
                    }
                },
            ],
            'items.*.theme' => ['required', 'in:'.implode(',', $this->themes)],
            'items.*.music' => ['required', 'in:'.implode(',', $this->music)],
            'items.*.custom_music' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a,aac', 'max:51200'],
            'items.*.existing_custom_music_path' => ['nullable', 'string', 'max:255'],
            'items.*.existing_custom_music_size_bytes' => ['nullable', 'integer'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.guide_audio' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a,aac', 'max:20480'],
            'items.*.existing_guide_audio_path' => ['nullable', 'string', 'max:255'],
            'items.*.existing_guide_audio_size_bytes' => ['nullable', 'integer'],
        ]);
    }

    private function syncItems(Request $request, ArtistPortfolio $portfolio, array $items): void
    {
        $oldAudioPaths = $portfolio->items()
            ->whereNotNull('guide_audio_path')
            ->pluck('guide_audio_path')
            ->all();
        $oldMusicPaths = $portfolio->items()
            ->whereNotNull('custom_music_path')
            ->pluck('custom_music_path')
            ->all();

        $portfolio->items()->delete();

        foreach ($items as $position => $item) {
            $audioPath = null;
            $audioSize = 0;
            $musicPath = null;
            $musicSize = 0;
            $fileKey = "items.{$position}.guide_audio";
            $musicFileKey = "items.{$position}.custom_music";

            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $audioSize = $file->getSize();
                $audioPath = $file->store('portfolio-guides', 'public');
            } elseif (! empty($item['existing_guide_audio_path']) && in_array($item['existing_guide_audio_path'], $oldAudioPaths, true)) {
                $audioPath = $item['existing_guide_audio_path'];
                $audioSize = (int) ($item['existing_guide_audio_size_bytes'] ?? 0);
                $oldAudioPaths = array_values(array_diff($oldAudioPaths, [$audioPath]));
            }

            if ($request->hasFile($musicFileKey)) {
                $file = $request->file($musicFileKey);
                $musicSize = $file->getSize();
                $musicPath = $file->store('portfolio-music', 'public');
            } elseif (! empty($item['existing_custom_music_path']) && in_array($item['existing_custom_music_path'], $oldMusicPaths, true)) {
                $musicPath = $item['existing_custom_music_path'];
                $musicSize = (int) ($item['existing_custom_music_size_bytes'] ?? 0);
                $oldMusicPaths = array_values(array_diff($oldMusicPaths, [$musicPath]));
            }

            $portfolio->items()->create([
                'illustration_id' => $item['illustration_id'],
                'position' => $position,
                'theme' => $item['theme'],
                'music' => $item['music'],
                'custom_music_path' => $musicPath,
                'custom_music_size_bytes' => $musicSize,
                'description' => $item['description'] ?? null,
                'guide_audio_path' => $audioPath,
                'guide_audio_size_bytes' => $audioSize,
            ]);
        }

        foreach ($oldAudioPaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        foreach ($oldMusicPaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
