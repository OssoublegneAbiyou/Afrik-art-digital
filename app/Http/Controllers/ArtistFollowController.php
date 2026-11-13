<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Notifications\MemberActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArtistFollowController extends Controller
{
    public function store(Request $request, Artist $artist): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $user = $request->user();
        $changes = $user->followedArtists()->syncWithoutDetaching([$artist->id]);

        // Notifie l'artiste seulement pour un vrai nouvel abonnement (pas un double clic, pas soi-même).
        if ($changes['attached'] && $artist->user && $artist->user_id !== $user->id) {
            $artist->user->notify(MemberActivity::newFollower($user, $artist));
        }

        return back()->with('success', 'Artiste suivi avec succès.');
    }

    public function destroy(Request $request, Artist $artist): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $request->user()->followedArtists()->detach($artist->id);

        return back()->with('success', 'Artiste retiré de vos suivis.');
    }
}
