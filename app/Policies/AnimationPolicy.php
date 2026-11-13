<?php

namespace App\Policies;

use App\Models\Animation;
use App\Models\User;

/**
 * Animations : seul l'auteur gère son animation dans l'espace artiste (l'équipe passe par l'administration).
 */
class AnimationPolicy
{
    public function manage(User $user, Animation $animation): bool
    {
        return $animation->user_id === $user->id;
    }
}
