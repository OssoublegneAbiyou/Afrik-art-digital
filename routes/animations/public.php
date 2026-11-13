<?php

use App\Http\Controllers\AnimationController;
use App\Http\Middleware\EnsureAnimationsEnabled;
use Illuminate\Support\Facades\Route;

// Pages publiques des animations (404 si la rubrique est désactivée : réglage anim.active).
// La liste est le filtre « Animé » de la galerie : /illustrations?format=anime (PublicController::illustrations).
Route::middleware(EnsureAnimationsEnabled::class)->group(function () {
    Route::get('/animations/{animation:slug}', [AnimationController::class, 'show'])->name('animations.show');

    // Comptage des vues (fetch JSON du lecteur, en-tête X-CSRF-TOKEN) : ouvert aux visiteurs, une vue par jour.
    Route::post('/animations/{animation:slug}/vue', [AnimationController::class, 'view'])
        ->middleware('throttle:30,1')
        ->name('animations.view');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/animations/{animation:slug}/favori', [AnimationController::class, 'favorite'])->name('animations.favorite');
    });
});
