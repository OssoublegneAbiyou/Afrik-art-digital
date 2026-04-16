<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\ViewErrorBag;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $errors = session('errors', new ViewErrorBag());

        if ($user->isVisitor()) {
            $followedArtists = $user->followedArtists()->with(['user', 'illustrations'])->latest()->get();
            $favoriteIllustrations = $user->favoriteIllustrations()->with('artist.user')->latest()->get();
            $bookmarkedDocuments = $user->bookmarkedDocuments()->with('writer.user')->latest()->get();

            $dashboardProps = [
                'accountType' => 'visitor',
                'userName' => $user->name,
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
                        'imageUrl' => asset('storage/'.$illustration->image_path),
                        'artistProfileUrl' => route('public.artist', $illustration->artist),
                        'unfavoriteUrl' => route('illustrations.unfavorite', $illustration),
                    ];
                })->values(),
                'bookmarkedDocuments' => $bookmarkedDocuments->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'title' => $document->title,
                        'writerName' => $document->writer?->user?->name ?? 'Ecrivain',
                        'coverImageUrl' => asset('storage/'.$document->cover_image_path),
                        'fileType' => strtoupper($document->file_type),
                        'fileUrl' => asset('storage/'.$document->file_path),
                        'writerProfileUrl' => route('public.writer', $document->writer),
                        'unbookmarkUrl' => route('documents.unbookmark', $document),
                    ];
                })->values(),
                'csrfToken' => csrf_token(),
                'successMessage' => session('success'),
            ];

            return view('dashboard', compact('dashboardProps'));
        }

        if ($user->isWriter()) {
            $writer = $user->writer()->firstOrCreate([]);
            $documents = $writer->documents()->latest()->get();

            $documentsData = $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'description' => $document->description,
                    'fileType' => strtoupper($document->file_type),
                    'fileSize' => $document->file_size_bytes,
                    'coverImageUrl' => asset('storage/'.$document->cover_image_path),
                    'fileUrl' => asset('storage/'.$document->file_path),
                    'destroyUrl' => route('documents.destroy', $document),
                ];
            })->values();

            $dashboardProps = [
                'accountType' => 'writer',
                'userName' => $user->name,
                'storageLimitBytes' => $user->storageLimitBytes(),
                'storageUsedBytes' => $user->storageUsedBytes(),
                'profileAction' => route('writer.profile.update'),
                'documentUploadAction' => route('documents.store'),
                'csrfToken' => csrf_token(),
                'successMessage' => session('success'),
                'quotaError' => $errors->first('quota'),
                'storageError' => $errors->first('storage'),
                'bio' => $writer->bio,
                'bannerUrl' => $writer->banner_path ? asset('storage/'.$writer->banner_path) : null,
                'social' => [
                    'instagram' => $writer->instagram,
                    'facebook' => $writer->facebook,
                    'website' => $writer->website,
                ],
                'documents' => $documentsData,
            ];

            return view('dashboard', compact('dashboardProps'));
        }

        $artist = $user->artist()->firstOrCreate([]);
        $illustrations = $artist->illustrations()->latest()->get();
        $illustrationsCount = $illustrations->count();
        $remaining = max(0, 20 - $illustrationsCount);

        $illustrationsData = $illustrations->map(function ($illustration) {
            return [
                'id' => $illustration->id,
                'title' => $illustration->title,
                'imageUrl' => asset('storage/' . $illustration->image_path),
                'destroyUrl' => route('illustrations.destroy', $illustration),
            ];
        })->values();

        $dashboardProps = [
            'accountType' => 'artist',
            'userName' => $user->name,
            'bio' => $artist->bio,
            'bannerUrl' => $artist->banner_path ? asset('storage/' . $artist->banner_path) : null,
            'illustrations' => $illustrationsData,
            'illustrationsCount' => $illustrationsCount,
            'remaining' => $remaining,
            'storageLimitBytes' => $user->storageLimitBytes(),
            'storageUsedBytes' => $user->storageUsedBytes(),
            'uploadAction' => route('illustrations.store'),
            'profileAction' => route('artist.profile.update'),
            'csrfToken' => csrf_token(),
            'quotaError' => $errors->first('quota'),
            'storageError' => $errors->first('storage'),
            'successMessage' => session('success'),
            'social' => [
                'instagram' => $artist->instagram,
                'twitter' => $artist->twitter,
                'facebook' => $artist->facebook,
                'youtube' => $artist->youtube,
                'behance' => $artist->behance,
                'website' => $artist->website,
            ],
        ];

        return view('dashboard', compact('dashboardProps'));
    }
}
