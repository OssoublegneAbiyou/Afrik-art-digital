<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Illustration;
use App\Models\User;
use App\View\Composers\AdminMenuComposer;
use App\View\Composers\CollectifComposer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Helper global setting('cle') : réglages du collectif.
        require_once app_path('helpers.php');
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Noms courts et stables pour les « J'aime » et signalements (colonnes *_type).
        Relation::morphMap([
            'illustration' => Illustration::class,
            'document' => Document::class,
            'comic' => \App\Models\Comic::class,
            'comic_chapter' => \App\Models\ComicChapter::class,
        ]);

        // Rôles d'administration (voir User::ROLES) :
        // - moderate : valider les œuvres, signalements, demandes d'adhésion, mises en avant ;
        // - manage-settings / manage-users : réglages du collectif, pages, annonces, comptes, journal.
        Gate::define('moderate', fn (User $user) => $user->isStaff());
        Gate::define('manage-settings', fn (User $user) => $user->isAdmin());
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());

        // $collectif : identité, contacts, réseaux… partagés avec toutes les vues.
        View::composer('*', CollectifComposer::class);
        // Menu de l'administration (pastilles « à traiter »).
        View::composer('layouts.admin', AdminMenuComposer::class);

        if (app()->environment('local') && request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
