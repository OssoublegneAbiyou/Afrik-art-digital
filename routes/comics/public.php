<?php

use App\Http\Controllers\ComicController;
use App\Http\Controllers\ComicReaderController;
use App\Http\Middleware\EnsureComicsEnabled;
use Illuminate\Support\Facades\Route;

// Pages publiques des récits dessinés (404 si la rubrique est désactivée : réglage bd.active).
Route::middleware(EnsureComicsEnabled::class)->group(function () {
    Route::get('/recits-dessines', [ComicController::class, 'index'])->name('comics.index');
    Route::get('/recits-dessines/{comic:slug}', [ComicController::class, 'show'])->name('comics.show');
    // Numéro de chapitre : « 2 » ou « 2.5 » (chapitre bonus).
    Route::get('/recits-dessines/{comic:slug}/lire/{numero}', [ComicReaderController::class, 'read'])
        ->where('numero', '[0-9]{1,4}(\.[0-9])?')
        ->name('comics.read');

    // Progression de lecture et comptage des vues (fetch JSON toutes les 5 s, sendBeacon à la fermeture).
    // Protégée par le jeton CSRF comme le reste du site : en-tête X-CSRF-TOKEN (fetch) ou champ _token
    // (FormData envoyée par sendBeacon). Ouverte aux visiteurs : ils n'enregistrent rien en base
    // (progression en localStorage) mais leur lecture compte une vue.
    Route::post('/lecture/progression', [ComicReaderController::class, 'progress'])
        ->middleware('throttle:60,1')
        ->name('comics.progress');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/recits-dessines/{comic:slug}/suivre', [ComicController::class, 'follow'])->name('comics.follow');
        Route::post('/recits-dessines/{comic:slug}/favori', [ComicController::class, 'favorite'])->name('comics.favorite');
    });
});
