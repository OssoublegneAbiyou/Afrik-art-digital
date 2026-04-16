<?php

namespace App\Http\Controllers;

use App\Models\Illustration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IllustrationFavoriteController extends Controller
{
    public function store(Request $request, Illustration $illustration): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $request->user()->favoriteIllustrations()->syncWithoutDetaching([$illustration->id]);

        return back()->with('success', 'Illustration ajoutee a vos favoris.');
    }

    public function destroy(Request $request, Illustration $illustration): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $request->user()->favoriteIllustrations()->detach($illustration->id);

        return back()->with('success', 'Illustration retiree de vos favoris.');
    }
}
