<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WriterController extends Controller
{
    public function updateProfile(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isWriter(), 403);

        $data = $request->validate([
            'bio' => ['nullable', 'string', 'max:3000'],
            'banner_image' => ['nullable', 'image', 'max:5120'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        $writer = $request->user()->writer()->firstOrCreate([]);
        $incomingBannerSize = $request->file('banner_image')?->getSize() ?? 0;
        $currentBannerSize = (int) $writer->banner_size_bytes;

        if (($request->user()->storageUsedBytes() - $currentBannerSize + $incomingBannerSize) > $request->user()->storageLimitBytes()) {
            return back()->withErrors([
                'storage' => 'Espace de stockage de 1 Go depasse. Supprime des fichiers avant d ajouter une nouvelle banniere.',
            ]);
        }

        $writer->fill(collect($data)->except('banner_image')->all());

        if ($request->hasFile('banner_image')) {
            if ($writer->banner_path && Storage::disk('public')->exists($writer->banner_path)) {
                Storage::disk('public')->delete($writer->banner_path);
            }

            $writer->banner_path = $request->file('banner_image')->store('banners/writers', 'public');
            $writer->banner_size_bytes = $incomingBannerSize;
        }

        $writer->save();

        return back()->with('success', 'Profil écrivain mis à jour.');
    }
}
