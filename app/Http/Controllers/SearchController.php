<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\Writer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Recherche globale : illustrations, textes, illustrateurs et écrivains.
 * Avec MySQL (collation utf8mb4_unicode_ci), la recherche ignore la casse et les accents.
 */
class SearchController extends Controller
{
    public const LIMIT = 24;

    public function __invoke(Request $request): View
    {
        $query = Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $request->query('q'))), 100, '');
        $results = null;

        if (mb_strlen($query) >= 2) {
            $like = '%'.addcslashes($query, '\\%_').'%';

            $illustrations = Illustration::query()
                ->with('artist.user')
                ->withCount('likes')
                ->whereHas('artist.user', fn (Builder $user) => $user->where('account_type', 'artist'))
                ->where(fn (Builder $q) => $q
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('tags', 'like', $like)
                    ->orWhereHas('artist.user', fn (Builder $user) => $user->where('name', 'like', $like)))
                ->latest();
            $illustrationsTotal = (clone $illustrations)->count();
            $illustrations = $illustrations->limit(self::LIMIT)->get();

            $documents = Document::query()
                ->with('writer.user')
                ->whereHas('writer.user', fn (Builder $user) => $user->where('account_type', 'writer'))
                ->where(fn (Builder $q) => $q
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('writer.user', fn (Builder $user) => $user->where('name', 'like', $like)))
                ->latest();
            $documentsTotal = (clone $documents)->count();
            $documents = $documents->limit(self::LIMIT)->get();

            // Récits dessinés (masqués si la rubrique est désactivée).
            $comics = collect();
            $comicsTotal = 0;
            if (\App\Http\Middleware\EnsureComicsEnabled::enabled()) {
                $comicsQuery = \App\Models\Comic::query()
                    ->withVisibleChapters()
                    ->with(['user', 'artist'])
                    ->withCount('chapters')
                    ->whereHas('user')
                    ->where(fn (Builder $q) => $q
                        ->where('titre', 'like', $like)
                        ->orWhere('resume', 'like', $like)
                        ->orWhere('tags', 'like', $like)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $like)))
                    ->orderByDesc('published_at');
                $comicsTotal = (clone $comicsQuery)->count();
                $comics = $comicsQuery->limit(self::LIMIT)->get();
            }

            // Animations prêtes (masquées si la rubrique est désactivée).
            $animations = collect();
            $animationsTotal = 0;
            if (\App\Http\Middleware\EnsureAnimationsEnabled::enabled()) {
                $animationsQuery = \App\Models\Animation::query()
                    ->listed()
                    ->with(['user', 'artist'])
                    ->where(fn (Builder $q) => $q
                        ->where('titre', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('tags', 'like', $like)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $like)))
                    ->orderByDesc('published_at');
                $animationsTotal = (clone $animationsQuery)->count();
                $animations = $animationsQuery->limit(self::LIMIT)->get();
            }

            $artists = Artist::query()
                ->with(['user', 'illustrations'])
                ->whereHas('user', fn (Builder $user) => $user->where('account_type', 'artist')->where('name', 'like', $like))
                ->where(fn (Builder $q) => $q->whereHas('user', fn (Builder $user) => $user->where('role', \App\Models\User::ROLE_MEMBER))->orHas('illustrations'))
                ->get();

            $writers = Writer::query()
                ->with(['user', 'documents'])
                ->whereHas('user', fn (Builder $user) => $user->where('account_type', 'writer')->where('name', 'like', $like))
                ->where(fn (Builder $q) => $q->whereHas('user', fn (Builder $user) => $user->where('role', \App\Models\User::ROLE_MEMBER))->orHas('documents'))
                ->get();

            $results = [
                'illustrations' => $illustrations,
                'illustrationsTotal' => $illustrationsTotal,
                'documents' => $documents,
                'documentsTotal' => $documentsTotal,
                'comics' => $comics,
                'comicsTotal' => $comicsTotal,
                'animations' => $animations,
                'animationsTotal' => $animationsTotal,
                'artists' => $artists->take(self::LIMIT),
                'writers' => $writers->take(self::LIMIT),
                'total' => $illustrationsTotal + $documentsTotal + $comicsTotal + $animationsTotal + $artists->count() + $writers->count(),
            ];
        }

        return view('search', ['query' => $query, 'results' => $results, 'limit' => self::LIMIT]);
    }
}
