<?php

namespace App\Policies;

use App\Models\Comic;
use App\Models\User;

/**
 * Récits dessinés : seul l'auteur gère son récit dans l'espace artiste (l'équipe passe par l'administration).
 */
class ComicPolicy
{
    public function manage(User $user, Comic $comic): bool
    {
        return $comic->user_id === $user->id;
    }
}
