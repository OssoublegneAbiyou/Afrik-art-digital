<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\FeaturedSelection;
use App\Models\Writer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class PublicController extends Controller
{
    public function index(): View
    {
        $artists = $this->artistsQuery()->get();
        $writers = $this->writersQuery()->get();
        $featured = FeaturedSelection::with(['artist.user', 'artist.illustrations', 'writer.user', 'writer.documents'])
            ->whereDate('featured_for', today())
            ->first();

        $featuredArtist = $featured?->artist ?: $this->pickDailyFeature($artists);
        $featuredWriter = $featured?->writer ?: $this->pickDailyFeature($writers);

        return view('public.index', compact('artists', 'writers', 'featuredArtist', 'featuredWriter'));
    }

    public function artists(): View
    {
        $artists = $this->artistsQuery()->get();
        $featured = FeaturedSelection::with(['artist.user', 'artist.illustrations'])
            ->whereDate('featured_for', today())
            ->first();
        $featuredArtist = $featured?->artist ?: $this->pickDailyFeature($artists);

        return view('public.artists', compact('artists', 'featuredArtist'));
    }

    public function writers(): View
    {
        $writers = $this->writersQuery()->get();
        $featured = FeaturedSelection::with(['writer.user', 'writer.documents'])
            ->whereDate('featured_for', today())
            ->first();
        $featuredWriter = $featured?->writer ?: $this->pickDailyFeature($writers);

        return view('public.writers', compact('writers', 'featuredWriter'));
    }

    public function showArtist(Artist $artist): View
    {
        $artist->load(['user', 'illustrations', 'portfolios.items.illustration']);

        $currentUser = auth()->user();
        $isFollowingArtist = $currentUser
            ? $currentUser->followedArtists()->where('artist_id', $artist->id)->exists()
            : false;
        $favoriteIllustrationIds = $currentUser
            ? $currentUser->favoriteIllustrations()
                ->whereIn('illustration_id', $artist->illustrations->pluck('id'))
                ->pluck('illustration_id')
                ->all()
            : [];

        return view('public.artist-show', compact('artist', 'isFollowingArtist', 'favoriteIllustrationIds'));
    }

    public function showWriter(Writer $writer): View
    {
        $writer->load(['user', 'documents']);

        $currentUser = auth()->user();
        $bookmarkedDocumentIds = $currentUser
            ? $currentUser->bookmarkedDocuments()
                ->whereIn('document_id', $writer->documents->pluck('id'))
                ->pluck('document_id')
                ->all()
            : [];

        return view('public.writer-show', compact('writer', 'bookmarkedDocumentIds'));
    }

    private function artistsQuery()
    {
        return Artist::with(['user', 'illustrations', 'portfolios'])
            ->whereHas('user', fn ($query) => $query->where('account_type', 'artist'))
            ->latest();
    }

    private function writersQuery()
    {
        return Writer::with(['user', 'documents'])
            ->whereHas('user', fn ($query) => $query->where('account_type', 'writer'))
            ->latest();
    }

    private function pickDailyFeature(Collection $items): Artist|Writer|null
    {
        if ($items->isEmpty()) {
            return null;
        }

        $index = now()->dayOfYear % $items->count();

        return $items->values()->get($index);
    }
}
