<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArtistController extends Controller
{
    public function updateProfile(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isArtist(), 403);

        $data = $request->validate([
            'bio' => ['nullable', 'string', 'max:3000'],
            'banner_image' => ['nullable', 'image', 'max:5120'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'twitter' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'youtube' => ['nullable', 'string', 'max:255'],
            'behance' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        $artist = $request->user()->artist()->firstOrCreate([]);
        $incomingBannerSize = $request->file('banner_image')?->getSize() ?? 0;
        $currentBannerSize = (int) $artist->banner_size_bytes;

        if (($request->user()->storageUsedBytes() - $currentBannerSize + $incomingBannerSize) > $request->user()->storageLimitBytes()) {
            return back()->withErrors([
                'storage' => 'Espace de stockage de 1 Go depasse. Supprime des fichiers avant d ajouter une nouvelle banniere.',
            ]);
        }

        $artist->fill(collect($data)->except('banner_image')->all());

        if ($request->hasFile('banner_image')) {
            if ($artist->banner_path && Storage::disk('public')->exists($artist->banner_path)) {
                Storage::disk('public')->delete($artist->banner_path);
            }

            $artist->banner_path = $request->file('banner_image')->store('banners/artists', 'public');
            $artist->banner_size_bytes = $incomingBannerSize;
        }

        $artist->save();

        return back()->with('success', 'Profil artiste mis a jour.');
    }
}
