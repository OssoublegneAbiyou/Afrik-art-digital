<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Illustration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IllustrationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isArtist(), 403);

        $artist = Artist::where('user_id', Auth::id())->first();

        if (! $artist) {
            abort(403);
        }

        if ($artist->illustrations()->count() >= 20) {
            return back()->withErrors([
                'quota' => 'Quota de 20 illustrations atteint. Supprime une œuvre pour en ajouter une nouvelle.',
            ]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:20480'],
        ]);

        $incomingSize = $request->file('image')->getSize();

        if (($request->user()->storageUsedBytes() + $incomingSize) > $request->user()->storageLimitBytes()) {
            return back()->withErrors([
                'storage' => 'Espace de stockage de 1 Go dépassé. Supprime des fichiers avant d’ajouter une nouvelle illustration.',
            ]);
        }

        $path = $request->file('image')->store('illustrations', 'public');

        Illustration::create([
            'artist_id' => $artist->id,
            'title' => $data['title'],
            'image_path' => $path,
            'file_size_bytes' => $incomingSize,
        ]);

        return back()->with('success', 'Illustration ajoutée avec succès.');
    }

    public function destroy(Illustration $illustration): RedirectResponse
    {
        if (auth()->user()?->isAdmin()) {
            if (Storage::disk('public')->exists($illustration->image_path)) {
                Storage::disk('public')->delete($illustration->image_path);
            }

            $illustration->delete();

            return back()->with('success', 'Illustration supprimee avec succes.');
        }

        $artist = Artist::where('user_id', Auth::id())->first();

        if (! $artist || $illustration->artist_id !== $artist->id) {
            abort(403, 'Non autorisé.');
        }

        if (Storage::disk('public')->exists($illustration->image_path)) {
            Storage::disk('public')->delete($illustration->image_path);
        }

        $illustration->delete();

        return back()->with('success', 'Illustration supprimée avec succès.');
    }
}
