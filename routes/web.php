<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\ArtistFollowController;
use App\Http\Controllers\ArtistPortfolioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentBookmarkController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\IllustrationController;
use App\Http\Controllers\IllustrationFavoriteController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\JoinRequestController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ThumbnailController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WriterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'index'])->name('public.index');
Route::get('/illustrateurs', [PublicController::class, 'artists'])->name('public.artists');
Route::get('/ecrivains', [PublicController::class, 'writers'])->name('public.writers');
Route::get('/illustrations', [PublicController::class, 'illustrations'])->name('public.illustrations');
Route::get('/oeuvres-litteraires', [PublicController::class, 'literaryWorks'])->name('public.literary-works');
Route::get('/artist/{artist}', [PublicController::class, 'showArtist'])->name('public.artist');
Route::get('/illustrations/{illustration}', [PublicController::class, 'showIllustration'])->name('public.illustration');
Route::get('/portfolios/{portfolio}', [ArtistPortfolioController::class, 'show'])->name('artist-portfolios.show');
Route::get('/writer/{writer}', [PublicController::class, 'showWriter'])->name('public.writer');
Route::get('/documents/{document}/lire', [DocumentController::class, 'read'])->name('documents.read');
Route::get('/documents/{document}/telecharger', [DocumentController::class, 'download'])->middleware('throttle:30,1')->name('documents.download');
Route::get('/documents/{document}/fichier', [DocumentController::class, 'file'])->name('documents.file');
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/miniatures/illustrations/{illustration}', [ThumbnailController::class, 'illustration'])->name('thumbnails.illustration');
    Route::get('/miniatures/couvertures/{document}', [ThumbnailController::class, 'cover'])->name('thumbnails.cover');
});
Route::get('/a-propos', [PublicController::class, 'about'])->name('public.about');
Route::get('/recherche', SearchController::class)->middleware('throttle:60,1')->name('search');
Route::post('/rejoindre', [JoinRequestController::class, 'store'])->middleware('throttle:3,60')->name('join-requests.store');
Route::post('/signaler/{type}/{id}', [ReportController::class, 'store'])
    ->whereIn('type', ['illustration', 'document', 'comic', 'comic_chapter'])->whereNumber('id')
    ->middleware('throttle:5,10')->name('reports.store');
Route::get('/mentions-legales', [\App\Http\Controllers\PageController::class, 'show'])->defaults('slug', 'mentions-legales')->name('public.legal');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/ma-collection', CollectionController::class)->name('collection');
    Route::post('/jaime/{type}/{id}', [LikeController::class, 'toggle'])
        ->whereIn('type', ['illustration', 'document', 'comic'])
        ->whereNumber('id')
        ->middleware('throttle:60,1')
        ->name('likes.toggle');

    Route::post('/illustrations', [IllustrationController::class, 'store'])->name('illustrations.store');
    Route::get('/mes-illustrations/{illustration}/modifier', [IllustrationController::class, 'edit'])->name('illustrations.edit');
    Route::patch('/illustrations/{illustration}', [IllustrationController::class, 'update'])->name('illustrations.update');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::delete('/notifications', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::delete('/illustrations/{illustration}', [IllustrationController::class, 'destroy'])->name('illustrations.destroy');
    Route::post('/artists/{artist}/follow', [ArtistFollowController::class, 'store'])->name('artists.follow');
    Route::delete('/artists/{artist}/follow', [ArtistFollowController::class, 'destroy'])->name('artists.unfollow');
    Route::post('/illustrations/{illustration}/favorite', [IllustrationFavoriteController::class, 'store'])->name('illustrations.favorite');
    Route::delete('/illustrations/{illustration}/favorite', [IllustrationFavoriteController::class, 'destroy'])->name('illustrations.unfavorite');

    Route::get('/mes-textes/nouveau', [DocumentController::class, 'create'])->name('documents.create');
    Route::get('/mes-textes/{document}/modifier', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::put('/documents/{document}', [DocumentController::class, 'save'])->name('documents.save');
    Route::patch('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::post('/documents/{document}/progression', [DocumentController::class, 'progress'])->middleware('throttle:120,1')->name('documents.progress');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/documents/{document}/bookmark', [DocumentBookmarkController::class, 'store'])->name('documents.bookmark');
    Route::delete('/documents/{document}/bookmark', [DocumentBookmarkController::class, 'destroy'])->name('documents.unbookmark');

    Route::patch('/artist/profile', [ArtistController::class, 'updateProfile'])->name('artist.profile.update');
    Route::patch('/writer/profile', [WriterController::class, 'updateProfile'])->name('writer.profile.update');

    Route::get('/mes-portfolios/create', [ArtistPortfolioController::class, 'create'])->name('artist-portfolios.create');
    Route::post('/mes-portfolios', [ArtistPortfolioController::class, 'store'])->name('artist-portfolios.store');
    Route::get('/mes-portfolios/{portfolio}/edit', [ArtistPortfolioController::class, 'edit'])->name('artist-portfolios.edit');
    Route::patch('/mes-portfolios/{portfolio}', [ArtistPortfolioController::class, 'update'])->name('artist-portfolios.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/comics.php';
require __DIR__.'/admin.php';
require __DIR__.'/auth.php';

// Pages éditables (admin › Pages), univers Pont. /mentions-legales est déclarée plus haut.
Route::get('/regles-de-publication', [\App\Http\Controllers\PageController::class, 'show'])->defaults('slug', 'regles-de-publication')->name('pages.rules');
Route::get('/confidentialite', [\App\Http\Controllers\PageController::class, 'show'])->defaults('slug', 'confidentialite')->name('pages.privacy');
Route::get('/pages/{slug}', [\App\Http\Controllers\PageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('pages.show');
