<?php

namespace App\Http\Controllers;

use App\Models\Illustration;
use App\Notifications\MemberActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IllustrationFavoriteController extends Controller
{
    public function store(Request $request, Illustration $illustration): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $user = $request->user();
        $changes = $user->favoriteIllustrations()->syncWithoutDetaching([$illustration->id]);
        $owner = $illustration->artist?->user;

        if ($changes['attached'] && $owner && $owner->id !== $user->id) {
            $owner->notify(MemberActivity::favorited($user, $illustration));
        }

        return back()->with('success', 'Illustration ajoutée à vos favoris.');
    }

    public function destroy(Request $request, Illustration $illustration): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $request->user()->favoriteIllustrations()->detach($illustration->id);

        return back()->with('success', 'Illustration retirée de vos favoris.');
    }
}
