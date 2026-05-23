<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArtistFollowController extends Controller
{
    public function store(Request $request, Artist $artist): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $request->user()->followedArtists()->syncWithoutDetaching([$artist->id]);

        return back()->with('success', 'Artiste suivi avec succes.');
    }

    public function destroy(Request $request, Artist $artist): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $request->user()->followedArtists()->detach($artist->id);

        return back()->with('success', 'Artiste retire de vos suivis.');
    }
}
