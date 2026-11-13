<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Comptes créés par l'équipe (admin « Membres » ou demande d'adhésion acceptée), suppression des fichiers
 * d'un compte, et protection du dernier administrateur actif.
 */
class MemberAccounts
{
    public const ACCOUNT_TYPES = [
        'artist' => 'Illustrateur',
        'writer' => 'Écrivain',
        'visitor' => 'Visiteur',
    ];

    /**
     * Crée le compte (e-mail vérifié, mot de passe aléatoire inutilisable) et son profil métier.
     * Le membre choisit son mot de passe via le lien envoyé par invite().
     */
    public static function create(string $name, string $email, string $accountType, string $role = User::ROLE_MEMBER): User
    {
        $user = new User([
            'name' => $name,
            'email' => strtolower(trim($email)),
            'account_type' => $accountType,
            'account_type_selected' => true,
            'role' => $role,
            'password' => Str::random(64),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        self::ensureProfile($user);

        return $user;
    }

    /**
     * Lien « Demander à rejoindre le collectif » avec le nom et l'e-mail préremplis (encart des visiteurs).
     */
    public static function joinUrl(?User $user = null): string
    {
        $query = $user ? ['nom' => $user->name, 'email' => $user->email] : [];

        return route('public.about', $query).'#rejoindre';
    }

    public static function ensureProfile(User $user): void
    {
        if ($user->isWriter()) {
            $user->writer()->firstOrCreate([]);
        } elseif ($user->isArtist()) {
            $user->artist()->firstOrCreate([]);
        }
    }

    /**
     * Envoie le lien de définition du mot de passe (jeton du broker « mot de passe oublié »).
     */
    public static function invite(User $user, bool $fromJoinRequest = false): bool
    {
        try {
            $token = Password::broker()->createToken($user);
            $user->notify(new AccountInvitation($token, $fromJoinRequest));

            return true;
        } catch (Throwable $exception) {
            Log::warning('Account invitation failed', ['user' => $user->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    public static function activeAdminCount(): int
    {
        return User::where('role', User::ROLE_ADMIN)->whereNull('deactivated_at')->count();
    }

    /**
     * Vrai si ce compte est le dernier administrateur actif (on ne peut ni le rétrograder, ni le désactiver, ni le supprimer).
     */
    public static function isLastActiveAdmin(User $user): bool
    {
        return $user->role === User::ROLE_ADMIN && ! $user->isDeactivated() && self::activeAdminCount() <= 1;
    }

    /**
     * Supprime du disque les fichiers d'un compte (bannières, images, manuscrits, sons des portfolios).
     */
    public static function deleteFiles(User $user): void
    {
        $user->loadMissing(['artist.allIllustrations', 'artist.portfolios.items', 'writer.allDocuments']);

        $paths = [];

        if ($user->artist) {
            $paths[] = $user->artist->banner_path;

            foreach ($user->artist->allIllustrations as $illustration) {
                $paths[] = $illustration->image_path;
                $paths[] = $illustration->thumbnail_path ?? null;
            }

            foreach ($user->artist->portfolios as $portfolio) {
                foreach ($portfolio->items as $item) {
                    $paths[] = $item->guide_audio_path;
                    $paths[] = $item->custom_music_path;
                }
            }
        }

        if ($user->writer) {
            $paths[] = $user->writer->banner_path;

            foreach ($user->writer->allDocuments as $document) {
                // Manuscrit (disque public ou privé selon l'ancienneté) et couverture.
                $document->deleteFiles();
            }
        }

        foreach (array_filter(array_unique($paths)) as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
