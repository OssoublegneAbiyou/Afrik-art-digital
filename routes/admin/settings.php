<?php

use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// Réglages du collectif (admins seulement). (Groupe : routes/admin.php)
Route::middleware('can:manage-settings')->group(function () {
    Route::get('/reglages', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::match(['put', 'post'], '/reglages', [SettingsController::class, 'update'])->name('settings.update');
});
