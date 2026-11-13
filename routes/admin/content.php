<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\FeaturedController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

// À valider, Signalements, Mises en avant, Pages, Annonces. (Groupe : routes/admin.php — Gate « moderate »)

// À valider (modérateurs et admins).
Route::get('/a-valider', [ModerationController::class, 'index'])->name('moderation.index');
Route::post('/a-valider/{type}/{id}/publier', [ModerationController::class, 'approve'])
    ->whereIn('type', ['illustration', 'document', 'comic', 'comic_chapter', 'animation'])->whereNumber('id')->name('moderation.approve');
Route::post('/a-valider/{type}/{id}/refuser', [ModerationController::class, 'reject'])
    ->whereIn('type', ['illustration', 'document', 'comic', 'comic_chapter', 'animation'])->whereNumber('id')->name('moderation.reject');

// Signalements (modérateurs et admins).
Route::get('/signalements', [ReportController::class, 'index'])->name('reports.index');
Route::patch('/signalements/{report}', [ReportController::class, 'resolve'])->name('reports.resolve');

// Mises en avant (modérateurs et admins).
Route::get('/mises-en-avant', [FeaturedController::class, 'index'])->name('featured.index');
Route::put('/mises-en-avant', [FeaturedController::class, 'update'])->name('featured.update');
Route::put('/mises-en-avant/epinglees', [FeaturedController::class, 'pinned'])->name('featured.pinned');
Route::post('/mises-en-avant/planning', [FeaturedController::class, 'schedule'])->name('featured.schedule');
Route::delete('/mises-en-avant/planning/{selection}', [FeaturedController::class, 'destroy'])->name('featured.destroy');

// Pages et annonces (admins).
Route::middleware('can:manage-settings')->group(function () {
    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{page}/modifier', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');

    Route::get('/annonces', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/annonces', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/annonces/{annonce}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::patch('/annonces/{annonce}/activation', [AnnouncementController::class, 'toggle'])->name('announcements.toggle');
    Route::delete('/annonces/{annonce}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});
