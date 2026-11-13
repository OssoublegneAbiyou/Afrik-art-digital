<?php

namespace App\Models\Concerns;

use App\Models\Like;
use App\Models\User;

/**
 * Œuvres pouvant recevoir des « J'aime » (illustrations, textes).
 */
trait HasLikes
{
    public static function bootHasLikes(): void
    {
        static::deleted(fn ($model) => $model->likes()->delete());
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Nombre de « J'aime » : utilise likes_count si la requête l'a chargé (withCount), sinon compte.
     */
    public function likesTotal(): int
    {
        return (int) ($this->likes_count ?? $this->likes()->count());
    }

    public function isLikedBy(?User $user): bool
    {
        return $user !== null && $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Ajoute ou retire le « J'aime » de ce membre. Renvoie true si l'œuvre est désormais aimée.
     */
    public function toggleLikeFor(User $user): bool
    {
        $existing = $this->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        $this->likes()->firstOrCreate(['user_id' => $user->id]);

        return true;
    }
}
