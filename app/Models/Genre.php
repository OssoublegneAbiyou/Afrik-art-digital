<?php

namespace App\Models;

use App\Support\SvgSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Genre littéraire géré par l'admin : un « ciel » de l'univers Songe (couleurs, phrase, emblème).
 * documents.genre contient le slug. Un genre inactif n'est plus proposé à l'envoi,
 * mais les récits existants gardent son libellé, son ciel et son emblème.
 */
class Genre extends Model
{
    // Liste mémorisée pour la requête en cours (dans le conteneur, pas en statique).
    private const MEMO = 'collectif.genres';

    // Emblème générique : livre ouvert.
    public const BOOK_EMBLEM = '<path d="M60 34 C48 26 30 26 18 30 V92 C30 88 48 88 60 96 C72 88 90 88 102 92 V30 C90 26 72 26 60 34 Z"/>'
        .'<path d="M60 34 V96"/>'
        .'<path d="M30 46 h18 M30 58 h18 M72 46 h18 M72 58 h18"/>';

    protected $fillable = [
        'slug', 'nom', 'phrase_accroche', 'description',
        'ciel_jour', 'encre_jour', 'ciel_nuit', 'encre_nuit', 'lueur_nuit',
        'emblem', 'ordre', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'actif' => 'boolean',
        ];
    }

    private ?array $emblemParts = null;

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

    public function documents()
    {
        return $this->hasMany(Document::class, 'genre', 'slug');
    }

    /**
     * Récits classés dans ce genre (brouillons, programmés et en attente compris).
     */
    public function usageCount(): int
    {
        return Document::withUnpublished()->where('genre', $this->slug)->count();
    }

    // ---------- Lecture mise en cache ----------

    /**
     * Tous les genres (actifs ou non), ordonnés, indexés par slug.
     *
     * @return Collection<string, Genre>
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

    /**
     * Genres actifs, ordonnés, indexés par slug.
     */
    public static function actifs(): Collection
    {
        return self::cached()->where('actif', true);
    }

    public static function findBySlug(?string $slug): ?self
    {
        return $slug === null || $slug === '' ? null : self::cached()->get($slug);
    }

    /**
     * Choix proposés à l'envoi : [slug => nom] des genres actifs, plus le genre actuel du récit
     * s'il a été désactivé depuis (pour qu'il reste sélectionné).
     *
     * @return array<string, string>
     */
    public static function options(?string $current = null): array
    {
        $options = self::actifs()->map(fn (self $genre) => $genre->nom)->all();

        if ($current !== null && ! isset($options[$current]) && ($genre = self::findBySlug($current))) {
            $options[$current] = $genre->nom;
        }

        return $options;
    }

    public static function labelFor(?string $slug): ?string
    {
        return self::findBySlug($slug)?->nom;
    }

    /**
     * Règle de validation : un genre actif, ou le genre actuel du récit.
     */
    public static function rule(?string $current = null): In
    {
        return Rule::in(array_keys(self::options($current)));
    }

    // ---------- Emblème ----------

    /**
     * @return array{viewBox: string, inner: string}
     */
    public function emblemParts(): array
    {
        return $this->emblemParts ??= SvgSanitizer::parts($this->emblem)
            ?? ['viewBox' => SvgSanitizer::DEFAULT_VIEWBOX, 'inner' => self::BOOK_EMBLEM];
    }

    /**
     * Emblème d'un genre (slug) ; livre ouvert si le genre est inconnu ou sans emblème.
     *
     * @return array{viewBox: string, inner: string}
     */
    public static function emblemFor(?string $slug): array
    {
        return self::findBySlug($slug)?->emblemParts()
            ?? ['viewBox' => SvgSanitizer::DEFAULT_VIEWBOX, 'inner' => self::BOOK_EMBLEM];
    }
}
