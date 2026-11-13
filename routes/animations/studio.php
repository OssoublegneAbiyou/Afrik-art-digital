<?php

use App\Http\Controllers\AnimationStudio\PublicationController;
use App\Http\Controllers\AnimationStudio\StudioController;
use App\Http\Middleware\EnsureAnimationStudio;
use Illuminate\Support\Facades\Route;

// Espace de l'illustrateur : envoi et gestion des animations.
// Masqué (404) si anim.active est faux ; réservé aux comptes illustrateurs ; chaque animation à son auteur (AnimationPolicy).
Route::middleware(['auth', 'verified', EnsureAnimationStudio::class])
    ->prefix('mes-animations')
    ->name('animations.studio.')
    ->group(function () {
        Route::get('/', [StudioController::class, 'index'])->name('index');
        Route::get('/nouvelle', [StudioController::class, 'create'])->name('create');
        Route::post('/', [StudioController::class, 'store'])->middleware('throttle:20,1')->name('store');
        Route::get('/{animation}/gerer', [StudioController::class, 'manage'])->name('manage');
        Route::get('/{animation}/modifier', [StudioController::class, 'edit'])->name('edit');
        Route::put('/{animation}', [StudioController::class, 'update'])->middleware('throttle:20,1')->name('update');
        Route::delete('/{animation}', [StudioController::class, 'destroy'])->name('destroy');

        Route::get('/{animation}/etat', [PublicationController::class, 'status'])->name('status');
        Route::post('/{animation}/relancer', [PublicationController::class, 'retry'])->name('retry');
        Route::post('/{animation}/poster', [PublicationController::class, 'poster'])->middleware('throttle:30,1')->name('poster');
        Route::post('/{animation}/publication', [PublicationController::class, 'publish'])->name('publish');
    });
