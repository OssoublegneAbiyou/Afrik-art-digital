<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JoinRequestController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MemberController;
use Illuminate\Support\Facades\Route;

// Tableau de bord, Membres, Demandes d'adhésion, Journal. (Groupe : routes/admin.php — auth, verified, can:moderate)

Route::get('/', DashboardController::class)->name('index');

// Demandes d'adhésion : modérateurs et admins.
Route::get('/demandes', [JoinRequestController::class, 'index'])->name('join-requests.index');
Route::get('/demandes/{joinRequest}', [JoinRequestController::class, 'show'])->name('join-requests.show');
Route::post('/demandes/{joinRequest}/accepter', [JoinRequestController::class, 'accept'])->name('join-requests.accept');
Route::post('/demandes/{joinRequest}/refuser', [JoinRequestController::class, 'refuse'])->name('join-requests.refuse');
Route::patch('/demandes/{joinRequest}/note', [JoinRequestController::class, 'updateNote'])->name('join-requests.note');

// Membres et journal : admins seulement.
Route::middleware('can:manage-users')->group(function () {
    Route::get('/membres', [MemberController::class, 'index'])->name('members.index');
    Route::post('/membres', [MemberController::class, 'store'])->name('members.store');
    Route::patch('/membres/{user}/role', [MemberController::class, 'updateRole'])->name('members.role');
    Route::post('/membres/{user}/desactiver', [MemberController::class, 'deactivate'])->name('members.deactivate');
    Route::post('/membres/{user}/reactiver', [MemberController::class, 'reactivate'])->name('members.reactivate');
    Route::delete('/membres/{user}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('/journal', [LogController::class, 'index'])->name('logs.index');
});
