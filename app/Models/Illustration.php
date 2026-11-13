<?php

namespace App\Models;

use App\Models\Concerns\HasLikes;
use App\Models\Concerns\HasPublication;
use App\Support\ImageThumbnail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Illustration extends Model
{
    use HasLikes;
    use HasPublication;

    // « Autre » : choix de repli à l'envoi et filtre de la galerie. Le filtre regroupe aussi
    // les œuvres sans type (publiées avant que le type devienne obligatoire) et celles d'un type
    // désactivé ou inconnu. Les autres types viennent de la table illustration_types (admin).
    public const OTHER_TYPE = 'autre';

    public const OTHER_LABEL = 'Autre';

    /**
     * Type utilisé pour le classement de la galerie : sans type, type désactivé ou inconnu → « Autre ».
     */
    public function effectiveType(): string
    {
        return $this->type && IllustrationType::actifs()->has($this->type) ? $this->type : self::OTHER_TYPE;
    }

    public const MAX_TAGS = 8;

    protected $fillable = [
        'artist_id',
        'title',
        'type',
        'description',
        'tags',
        'image_path',
        'thumbnail_path',
        'file_size_bytes',
        'published_at',
        'moderation_status',
        'moderation_note',
        'moderated_at',
        'rights_certified_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'view_count' => 'integer',
        ];
    }

    /**
     * Mots-clés stockés lisibles (« été » et non « été ») pour que la recherche les retrouve.
     */
    protected function asJson($value, $flags = 0)
    {
        return parent::asJson($value, $flags | JSON_UNESCAPED_UNICODE);
    }

    public function authorUser(): ?User
    {
        return $this->artist?->user;
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->artist?->user_id === $user->id;
    }

    /**
     * « manga, Abidjan , nuit » → ['manga', 'abidjan', 'nuit'] (minuscules, sans doublon, 8 au plus).
     */
    public static function normalizeTags(?string $input): array
    {
        return collect(preg_split('/[,;#\n]+/u', (string) $input))
            ->map(fn ($tag) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $tag))))
            ->filter(fn ($tag) => $tag !== '' && mb_strlen($tag) <= 30)
            ->unique()
            ->take(self::MAX_TAGS)
            ->values()
            ->all();
    }

    protected static function booted(): void
    {
        static::created(function (Illustration $illustration) {
            $illustration->generateThumbnail();
        });

        static::deleted(function (Illustration $illustration) {
            ImageThumbnail::delete($illustration->image_path);

            // Ancien emplacement (colonne thumbnail_path, versions précédentes).
            if ($illustration->thumbnail_path) {
                Storage::disk('public')->delete($illustration->thumbnail_path);
            }
        });
    }

    // Une illustration appartient à un artiste
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'illustration_favorites')->withTimestamps();
    }

    public function portfolioItems()
    {
        return $this->hasMany(ArtistPortfolioItem::class);
    }

    public function typeLabel(): ?string
    {
        // Un type désactivé garde son libellé sur les œuvres existantes.
        return IllustrationType::labelFor($this->type);
    }

    public function generateThumbnail(): ?string
    {
        return ImageThumbnail::generate($this->image_path);
    }

    /**
     * Image d'origine : fiche œuvre, plein écran, portfolio immersif.
     */
    public function imageUrl(): string
    {
        return asset('storage/'.$this->image_path);
    }

    /**
     * Miniature WebP ≈ 600 px pour les grilles et vignettes, générée à la première demande si besoin.
     */
    public function thumbnailUrl(): string
    {
        return ImageThumbnail::url($this->image_path, route('thumbnails.illustration', $this));
    }
}
