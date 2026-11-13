<?php

use Illuminate\Support\Facades\Route;

/*
| Administration du collectif (/admin). Tout le groupe exige un compte de l'équipe (Gate « moderate » :
| modérateur ou admin). Les écrans réservés aux admins ajoutent ->middleware('can:manage-settings')
| ou ->middleware('can:manage-users') sur leurs routes.
| Noms des routes : préfixe « admin. » ajouté automatiquement.
*/
Route::middleware(['auth', 'verified', 'can:moderate'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        require __DIR__.'/admin/community.php';  // tableau de bord, membres, demandes d'adhésion, journal
        require __DIR__.'/admin/content.php';    // à valider, signalements, mises en avant, pages, annonces
        require __DIR__.'/admin/settings.php';   // réglages du collectif
        require __DIR__.'/admin/taxonomy.php';   // types d'illustration et genres littéraires
    });
