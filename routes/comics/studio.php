<?php

use App\Http\Controllers\ComicStudio\ChapterController;
use App\Http\Controllers\ComicStudio\PageController;
use App\Http\Controllers\ComicStudio\StudioController;
use App\Http\Middleware\EnsureComicStudio;
use Illuminate\Support\Facades\Route;

// Espace de l'illustrateur : création et gestion des récits dessinés.
// Masqué (404) si bd.active est faux ; réservé aux comptes illustrateurs ; chaque récit à son auteur (ComicPolicy).
Route::middleware(['auth', 'verified', EnsureComicStudio::class])
    ->prefix('mes-recits-dessines')
    ->name('comics.studio.')
    ->group(function () {
        Route::get('/', [StudioController::class, 'index'])->name('index');
        Route::get('/nouveau', [StudioController::class, 'create'])->name('create');
        Route::post('/', [StudioController::class, 'store'])->name('store');
        Route::get('/{comic}/gerer', [StudioController::class, 'manage'])->name('manage');
        Route::get('/{comic}/fiche', [StudioController::class, 'edit'])->name('edit');
        Route::put('/{comic}', [StudioController::class, 'update'])->name('update');
        Route::delete('/{comic}', [StudioController::class, 'destroy'])->name('destroy');

        Route::get('/{comic}/chapitres/nouveau', [ChapterController::class, 'create'])->name('chapters.create');
        Route::post('/{comic}/chapitres', [ChapterController::class, 'store'])->name('chapters.store');
        Route::get('/{comic}/chapitres/{chapter}/pages', [ChapterController::class, 'pages'])->whereNumber('chapter')->name('chapters.pages');
        Route::put('/{comic}/chapitres/{chapter}', [ChapterController::class, 'update'])->whereNumber('chapter')->name('chapters.update');
        Route::delete('/{comic}/chapitres/{chapter}', [ChapterController::class, 'destroy'])->whereNumber('chapter')->name('chapters.destroy');
        Route::get('/{comic}/chapitres/{chapter}/publication', [ChapterController::class, 'publication'])->whereNumber('chapter')->name('chapters.publication');
        Route::post('/{comic}/chapitres/{chapter}/publication', [ChapterController::class, 'publish'])->whereNumber('chapter')->name('chapters.publish');

        // Planches (appels AJAX de l'assistant).
        Route::prefix('/{comic}/chapitres/{chapter}/planches')->whereNumber('chapter')->name('pages.')->group(function () {
            Route::post('/', [PageController::class, 'store'])->middleware('throttle:240,1')->name('store');
            Route::get('/etat', [PageController::class, 'status'])->name('status');
            Route::put('/ordre', [PageController::class, 'reorder'])->name('reorder');
            Route::post('/tri-par-nom', [PageController::class, 'sort'])->name('sort');
            Route::patch('/{page}', [PageController::class, 'update'])->whereNumber('page')->name('update');
            Route::post('/{page}/remplacer', [PageController::class, 'replace'])->whereNumber('page')->name('replace');
            Route::delete('/{page}', [PageController::class, 'destroy'])->whereNumber('page')->name('destroy');
        });
    });
