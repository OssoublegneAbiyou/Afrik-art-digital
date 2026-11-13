<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Annonce affichée en bandeau fin en haut de toutes les pages publiques (une seule à la fois :
 * la plus récente parmi les annonces actives et dans leur période).
 */
class Annonce extends Model
{
    public const CACHE_KEY = 'annonces.courante';

    public const TEXT_MAX = 140;

    protected $table = 'annonces';

    protected $fillable = ['texte', 'lien', 'debut', 'fin', 'actif'];

    protected function casts(): array
    {
        return [
            'debut' => 'datetime',
            'fin' => 'datetime',
            'actif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Annonces actives et dans leur période d'affichage.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('actif', true)
            ->where(fn ($q) => $q->whereNull('debut')->orWhere('debut', '<=', now()))
            ->where(fn ($q) => $q->whereNull('fin')->orWhere('fin', '>=', now()));
    }

    /**
     * Annonce à afficher (cache d'une minute, vidé à chaque enregistrement) : ['id', 'texte', 'lien'] ou null.
     */
    public static function current(): ?array
    {
        try {
            // Tableau englobant : un « aucune annonce » est lui aussi mis en cache.
            $cached = Cache::remember(self::CACHE_KEY, 60, fn () => [
                'annonce' => static::active()->latest()->latest('id')->first()?->only(['id', 'texte', 'lien']),
            ]);
        } catch (Throwable) {
            return null;
        }

        return $cached['annonce'] ?? null;
    }

    public function isLive(): bool
    {
        return $this->actif
            && ($this->debut === null || $this->debut->lte(now()))
            && ($this->fin === null || $this->fin->gte(now()));
    }

    public function statusLabel(): string
    {
        return match (true) {
            ! $this->actif => 'Inactive',
            $this->debut && $this->debut->isFuture() => 'Programmée',
            $this->fin && $this->fin->isPast() => 'Terminée',
            default => 'En ligne',
        };
    }

    public function isExternal(): bool
    {
        return $this->lien !== null && str_starts_with($this->lien, 'http') && ! str_starts_with($this->lien, url('/'));
    }
}
