<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// À lancer une fois après le déploiement : les manuscrits envoyés avant le téléchargement contrôlé
// sont encore sur le disque public (lien direct /storage/...). Cette commande les rend privés.
Artisan::command('documents:secure', function () {
    $moved = 0;

    \App\Models\Document::where('file_disk', 'public')->whereNotNull('file_path')->each(function (\App\Models\Document $document) use (&$moved) {
        $public = \Illuminate\Support\Facades\Storage::disk('public');
        $private = \Illuminate\Support\Facades\Storage::disk(\App\Models\Document::PRIVATE_DISK);

        if (! $public->exists($document->file_path)) {
            $this->warn("· {$document->title} : fichier introuvable, ignoré");

            return;
        }

        $private->writeStream($document->file_path, $public->readStream($document->file_path));
        $document->forceFill(['file_disk' => \App\Models\Document::PRIVATE_DISK])->saveQuietly();
        $public->delete($document->file_path);
        $moved++;
        $this->line("· {$document->title} : protégé");
    });

    $this->info(\App\Support\Fr::count($moved, 'manuscrit déplacé', 'manuscrits déplacés').' vers le stockage privé.');
})->purpose('Déplace les manuscrits existants vers le stockage privé (téléchargement contrôlé)');

// Donne le rôle d'administrateur à un compte existant (premier admin, ou secours si plus personne n'y a accès).
Artisan::command('collectif:make-admin {email : E-mail du compte}', function (string $email) {
    $user = \App\Models\User::where('email', $email)->first();

    if (! $user) {
        $this->error("Aucun compte avec l'e-mail {$email}. Créez d'abord le compte sur le site.");

        return 1;
    }

    $before = $user->role;
    $user->forceFill(['role' => \App\Models\User::ROLE_ADMIN, 'deactivated_at' => null])->save();
    \App\Support\AdminLogger::log('role.modifie', $user, null, ['role' => $before], ['role' => $user->role, 'via' => 'console']);

    $this->info("{$user->name} est maintenant administrateur·rice du collectif.");
})->purpose('Donne le rôle administrateur à un compte (par e-mail)');

// Récits dessinés : sortie des chapitres programmés (abonnés prévenus une fois) — planifiée chaque minute.
Artisan::command('comics:release-scheduled', function () {
    $result = \App\Support\ComicPublisher::releaseScheduled();

    $this->info(\App\Support\Fr::count($result['released'], 'chapitre publié', 'chapitres publiés')
        .', '.\App\Support\Fr::count($result['warned'], 'auteur prévenu', 'auteurs prévenus').' (validation en attente).');
})->purpose('Publie les chapitres programmés arrivés à échéance et prévient les abonnés');

\Illuminate\Support\Facades\Schedule::command('comics:release-scheduled')->everyMinute()->withoutOverlapping();

// Facultatif : les miniatures sont aussi créées automatiquement à la première visite.
Artisan::command('illustrations:thumbnails {--force : Recrée les miniatures existantes}', function () {
    if (! function_exists('imagecreatefromstring')) {
        $this->error('L’extension PHP GD est absente : impossible de créer les miniatures.');

        return 1;
    }

    $sources = \App\Models\Illustration::get(['title', 'image_path'])
        ->map(fn ($illustration) => [$illustration->title, $illustration->image_path])
        ->concat(\App\Models\Document::get(['title', 'cover_image_path'])
            ->map(fn ($document) => ['Couverture · '.$document->title, $document->cover_image_path]));

    $done = 0;
    foreach ($sources as [$title, $path]) {
        if ($this->option('force')) {
            \App\Support\ImageThumbnail::delete($path);
        }

        $thumbnail = \App\Support\ImageThumbnail::generate($path);
        $done += $thumbnail ? 1 : 0;
        $this->line("· {$title} : ".($thumbnail ?: 'échec'));
    }

    $this->info(\App\Support\Fr::count($done, 'miniature prête', 'miniatures prêtes').' sur '.$sources->count().'.');
})->purpose('Génère les miniatures WebP (600 px) des illustrations et des couvertures');
