<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\IllustrationType;
use App\Models\PlatformSetting;
use App\Support\PlatformRules;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ViewErrorBag;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $errors = session('errors', new ViewErrorBag());
        $themes = PlatformSetting::dashboardThemes();

        if ($user->isVisitor()) {
            $followedArtists = $user->followedArtists()->with(['user', 'illustrations'])->latest()->get();
            $favoriteIllustrations = $user->favoriteIllustrations()->with('artist.user')->latest()->get();
            $bookmarkedDocuments = $user->bookmarkedDocuments()->with('writer.user')->latest()->get();

            $dashboardProps = [
                'accountType' => 'visitor',
                'userName' => $user->name,
                'accountSettingsUrl' => route('profile.edit'),
                // Encart « Envie de publier ? » : demande d'adhésion préremplie (nom, e-mail).
                'joinUrl' => setting('adhesion.ouverte', true) ? \App\Support\MemberAccounts::joinUrl($user) : null,
                'followedArtists' => $followedArtists->map(function ($artist) {
                    return [
                        'id' => $artist->id,
                        'name' => $artist->user?->name ?? 'Artiste',
                        'bio' => $artist->bio,
                        'profileUrl' => route('public.artist', $artist),
                        'illustrationsCount' => $artist->illustrations->count(),
                        'bannerUrl' => $artist->banner_path ? asset('storage/'.$artist->banner_path) : null,
                    ];
                })->values(),
                'favoriteIllustrations' => $favoriteIllustrations->map(function ($illustration) {
                    return [
                        'id' => $illustration->id,
                        'title' => $illustration->title,
                        'artistName' => $illustration->artist?->user?->name ?? 'Artiste',
                        'imageUrl' => $illustration->thumbnailUrl(),
                        'artistProfileUrl' => route('public.artist', $illustration->artist),
                        'unfavoriteUrl' => route('illustrations.unfavorite', $illustration),
                    ];
                })->values(),
                'bookmarkedDocuments' => $bookmarkedDocuments->map(function ($document) use ($user) {
                    return [
                        'id' => $document->id,
                        'title' => $document->title,
                        'writerName' => $document->writer?->user?->name ?? 'Écrivain',
                        'coverImageUrl' => $document->coverThumbnailUrl(),
                        'fileType' => strtoupper($document->file_type),
                        'fileUrl' => $document->canBeDownloadedBy($user) ? route('documents.download', $document) : null,
                        'readUrl' => route('documents.read', $document),
                        'writerProfileUrl' => route('public.writer', $document->writer),
                        'unbookmarkUrl' => route('documents.unbookmark', $document),
                    ];
                })->values(),
                'csrfToken' => csrf_token(),
                'successMessage' => session('success'),
                'theme' => $themes['visitor'],
            ];

            return view('dashboard', compact('dashboardProps'));
        }

        if ($user->isWriter()) {
            $writer = $user->writer()->firstOrCreate([]);
            $documents = $writer->allDocuments()->withCount('likes')->latest()->get();

            $documentsData = $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'description' => $document->description,
                    'fileType' => strtoupper($document->file_type),
                    'fileSize' => $document->file_size_bytes,
                    'genre' => $document->genre,
                    'genreLabel' => $document->genreLabel(),
                    // Genre désactivé depuis : il reste proposé pour ce récit.
                    'genreOptions' => Genre::options($document->genre),
                    'genreUpdateUrl' => route('documents.update', $document),
                    'coverImageUrl' => $document->coverThumbnailUrl(),
                    'fileUrl' => route('documents.download', $document),
                    'editUrl' => route('documents.edit', $document),
                    'downloadCount' => (int) $document->download_count,
                    'viewCount' => (int) $document->view_count,
                    'likesCount' => (int) $document->likes_count,
                    'statusLabel' => $document->statusLabel(),
                    'moderationNote' => $document->isRejected() ? $document->moderation_note : null,
                    'isPublished' => $document->isVisible(),
                    'allowDownload' => $document->downloadOpenToReaders(),
                    'hasText' => filled($document->content) || in_array(strtolower((string) $document->file_type), ['txt', 'docx'], true),
                    'readUrl' => route('documents.read', $document),
                    'destroyUrl' => route('documents.destroy', $document),
                ];
            })->values();

            $dashboardProps = [
                'accountType' => 'writer',
                'userName' => $user->name,
                'accountSettingsUrl' => route('profile.edit'),
                'storageLimitBytes' => $user->storageLimitBytes(),
                'storageUsedBytes' => $user->storageUsedBytes(),
                'storageLimitLabel' => PlatformRules::storageLabel(),
                'profileAction' => route('writer.profile.update'),
                'documentUploadAction' => route('documents.store'),
                'documentCreateUrl' => route('documents.create'),
                'moderationEnabled' => PlatformSetting::moderationEnabled() && ! $user->isStaff(),
                'documentGenres' => Genre::options(),
                'csrfToken' => csrf_token(),
                'successMessage' => session('success'),
                'quotaError' => $errors->first('quota'),
                'storageError' => $errors->first('storage'),
                'uploadError' => $errors->first('rights') ?: ($errors->first('genre') ?: ($errors->first('document') ?: ($errors->first('cover_image') ?: $errors->first('title')))),
                'bio' => $writer->bio,
                'bannerUrl' => $writer->banner_path ? asset('storage/'.$writer->banner_path) : null,
                'social' => [
                    'instagram' => $writer->instagram,
                    'facebook' => $writer->facebook,
                    'website' => $writer->website,
                ],
                'documents' => $documentsData,
                'theme' => $themes['writer'],
            ];

            return view('dashboard', compact('dashboardProps'));
        }

        // Espace artiste : vue Blade dans l'univers Néo-Babi.
        $artist = $user->artist()->firstOrCreate([]);
        // Brouillons et publications programmées compris, avec les statistiques de chaque œuvre.
        $illustrations = $artist->allIllustrations()->withCount(['likes', 'favoritedBy'])->latest()->get();
        $portfolios = $artist->portfolios()->withCount('items')->latest()->get();
        $storageUsedBytes = $user->storageUsedBytes();
        $storageLimitBytes = $user->storageLimitBytes();
        $artist->loadCount('followers');

        return view('dashboard.artist', [
            'user' => $user,
            'artist' => $artist,
            'illustrations' => $illustrations,
            'stats' => [
                'views' => $illustrations->sum('view_count'),
                'likes' => $illustrations->sum('likes_count'),
                'favorites' => $illustrations->sum('favorited_by_count'),
                'followers' => (int) $artist->followers_count,
                'drafts' => $illustrations->filter->isDraft()->count(),
                'scheduled' => $illustrations->filter->isScheduled()->count(),
            ],
            'maxBatch' => PlatformRules::imagesPerUpload(),
            'maxImageMb' => PlatformRules::maxImageMb(),
            'untypedCount' => $illustrations->whereNull('type')->count(),
            'portfolios' => $portfolios,
            'quota' => PlatformRules::maxWorks(),
            'remaining' => max(0, PlatformRules::maxWorks() - $illustrations->count()),
            'storageUsedBytes' => $storageUsedBytes,
            'storageLimitBytes' => $storageLimitBytes,
            'storageLimitLabel' => PlatformRules::storageLabel(),
            'storageRatio' => $storageLimitBytes > 0 ? min(100, (int) round($storageUsedBytes / $storageLimitBytes * 100)) : 0,
            'types' => IllustrationType::options(),
        ]);
    }
}
