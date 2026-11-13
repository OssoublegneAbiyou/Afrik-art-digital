<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Type d'illustration géré par l'admin (Croquis, Couleur, Couverture…). illustrations.type contient le slug.
 * « Autre » (Illustration::OTHER_TYPE) n'est pas dans la table : c'est le choix et le filtre de repli,
 * qui regroupent aussi les œuvres sans type ou d'un type désactivé / inconnu.
 */
class IllustrationType extends Model
{
    private const MEMO = 'collectif.illustration_types';

    protected $fillable = ['slug', 'nom', 'ordre', 'actif'];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'actif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeOrdonne(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('nom')->orderBy('id');
    }

    /**
     * Illustrations de ce type (brouillons, programmées et en attente comprises).
     */
    public function usageCount(): int
    {
        return Illustration::withUnpublished()->where('type', $this->slug)->count();
    }

    // ---------- Lecture mise en cache ----------

    /**
     * Tous les types (actifs ou non), ordonnés, indexés par slug.
     *
     * @return Collection<string, IllustrationType>
     */
    public static function cached(): Collection
    {
        $app = app();

        if (! $app->bound(self::MEMO)) {
            $app->instance(self::MEMO, static::query()->ordonne()->get()->keyBy('slug'));
        }

        return $app->make(self::MEMO);
    }

    public static function flushCache(): void
    {
        app()->forgetInstance(self::MEMO);
    }

    public static function actifs(): Collection
    {
        return self::cached()->where('actif', true);
    }

    public static function findBySlug(?string $slug): ?self
    {
        return $slug === null || $slug === '' ? null : self::cached()->get($slug);
    }

    /**
     * Choix proposés à l'envoi : types actifs, puis « Autre » ; plus le type actuel de l'œuvre
     * s'il a été désactivé depuis.
     *
     * @return array<string, string>
     */
    public static function options(?string $current = null): array
    {
        $options = self::actifs()->map(fn (self $type) => $type->nom)->all();

        if ($current !== null && ! isset($options[$current]) && ($type = self::findBySlug($current))) {
            $options[$current] = $type->nom;
        }

        return $options + [Illustration::OTHER_TYPE => Illustration::OTHER_LABEL];
    }

    /**
     * Filtres de la galerie : types actifs puis « Autre ».
     *
     * @return array<string, string>
     */
    public static function filters(): array
    {
        return self::actifs()->map(fn (self $type) => $type->nom)->all()
            + [Illustration::OTHER_TYPE => Illustration::OTHER_LABEL];
    }

    public static function labelFor(?string $slug): ?string
    {
        if ($slug === Illustration::OTHER_TYPE) {
            return Illustration::OTHER_LABEL;
        }

        return self::findBySlug($slug)?->nom;
    }

    public static function rule(?string $current = null): In
    {
        return Rule::in(array_keys(self::options($current)));
    }
}
