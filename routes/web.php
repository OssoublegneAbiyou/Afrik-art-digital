<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\ArtistFollowController;
use App\Http\Controllers\ArtistPortfolioController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentBookmarkController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\IllustrationController;
use App\Http\Controllers\IllustrationFavoriteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WriterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'index'])->name('public.index');
Route::get('/illustrateurs', [PublicController::class, 'artists'])->name('public.artists');
Route::get('/ecrivains', [PublicController::class, 'writers'])->name('public.writers');
Route::get('/artist/{artist}', [PublicController::class, 'showArtist'])->name('public.artist');
Route::get('/portfolios/{portfolio}', [ArtistPortfolioController::class, 'show'])->name('artist-portfolios.show');
Route::get('/writer/{writer}', [PublicController::class, 'showWriter'])->name('public.writer');
Route::get('/documents/{document}/lire', [DocumentController::class, 'read'])->name('documents.read');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::post('/admin/featured', [AdminController::class, 'updateFeatured'])->name('admin.featured.update');
    Route::post('/admin/dashboard-themes', [AdminController::class, 'updateDashboardThemes'])->name('admin.dashboard-themes.update');

    Route::post('/illustrations', [IllustrationController::class, 'store'])->name('illustrations.store');
    Route::delete('/illustrations/{illustration}', [IllustrationController::class, 'destroy'])->name('illustrations.destroy');
    Route::post('/artists/{artist}/follow', [ArtistFollowController::class, 'store'])->name('artists.follow');
    Route::delete('/artists/{artist}/follow', [ArtistFollowController::class, 'destroy'])->name('artists.unfollow');
    Route::post('/illustrations/{illustration}/favorite', [IllustrationFavoriteController::class, 'store'])->name('illustrations.favorite');
    Route::delete('/illustrations/{illustration}/favorite', [IllustrationFavoriteController::class, 'destroy'])->name('illustrations.unfavorite');

    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
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

require __DIR__.'/auth.php';
