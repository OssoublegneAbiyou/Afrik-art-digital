<?php

use App\Http\Controllers\Admin\TaxonomyController;
use Illuminate\Support\Facades\Route;

// Types d'illustration et genres littéraires. (Groupe : routes/admin.php)
Route::middleware('can:manage-settings')
    ->prefix('types-genres')
    ->name('taxonomy.')
    ->controller(TaxonomyController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');

        Route::post('/types', 'storeType')->name('types.store');
        Route::put('/types/{illustrationType}', 'updateType')->name('types.update');
        Route::post('/types/{illustrationType}/deplacer', 'moveType')->name('types.move');
        Route::delete('/types/{illustrationType}', 'destroyType')->name('types.destroy');

        Route::get('/genres/nouveau', 'createGenre')->name('genres.create');
        Route::post('/genres', 'storeGenre')->name('genres.store');
        Route::post('/genres/apercu', 'previewEmblem')->name('genres.preview');
        Route::get('/genres/{genre}', 'editGenre')->name('genres.edit');
        Route::put('/genres/{genre}', 'updateGenre')->name('genres.update');
        Route::post('/genres/{genre}/deplacer', 'moveGenre')->name('genres.move');
        Route::delete('/genres/{genre}', 'destroyGenre')->name('genres.destroy');
    });
