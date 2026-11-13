<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * « Ma collection » : favoris, marque-pages et artistes suivis, pour tous les types de compte.
 */
class CollectionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Récits dessinés : séries suivies (avec la reprise de lecture) et favoris.
        $followedComics = collect();
        $favoriteComics = collect();
        $comicResume = [];
        if (\App\Http\Middleware\EnsureComicsEnabled::enabled()) {
            $followedComics = \App\Models\Comic::query()
                ->withVisibleChapters()
                ->whereHas('followers', fn ($q) => $q->where('users.id', $user->id))
                ->with(['user', 'artist', 'chapters'])
                ->withCount('chapters')
                ->orderByRaw('coalesce(last_chapter_at, published_at) desc')
                ->get();
            $progress = \App\Models\ComicReadingProgress::where('user_id', $user->id)
                ->whereIn('comic_chapter_id', $followedComics->flatMap->chapters->pluck('id'))
                ->get();
            foreach ($followedComics as $comic) {
                $comic->chapters->each->setRelation('comic', $comic);
                $comicResume[$comic->id] = ComicController::resumePoint(
                    $comic->chapters,
                    $progress->whereIn('comic_chapter_id', $comic->chapters->pluck('id'))
                );
            }
            $favoriteComics = \App\Models\Comic::query()
                ->withVisibleChapters()
                ->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))
                ->with(['user', 'artist'])
                ->withCount('chapters')
                ->latest()
                ->get();
        }

        // Animations favorites (publiées, validées et prêtes).
        $favoriteAnimations = \App\Http\Middleware\EnsureAnimationsEnabled::enabled()
            ? \App\Models\Animation::query()->listed()
                ->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))
                ->with(['user', 'artist'])
                ->latest('published_at')
                ->get()
            : collect();

        return view('collection', [
            'favoriteAnimations' => $favoriteAnimations,
            'followedComics' => $followedComics,
            'favoriteComics' => $favoriteComics,
            'comicResume' => $comicResume,
            'favorites' => $user->favoriteIllustrations()->with('artist.user')->latest('illustration_favorites.created_at')->get(),
            'bookmarks' => $user->bookmarkedDocuments()->with('writer.user')->latest('document_bookmarks.created_at')->get(),
            'followedArtists' => $user->followedArtists()->with(['user', 'illustrations'])->latest('artist_follows.created_at')->get(),
            // Où le lecteur s'est arrêté dans chaque texte (document_id => ReadingProgress).
            'progress' => \App\Models\ReadingProgress::where('user_id', $user->id)->get()->keyBy('document_id'),
        ]);
    }
}
