<?php

namespace App\Models;

use App\Models\Concerns\HasLikes;
use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Récit dessiné : one-shot (un seul chapitre, numéro 1, invisible pour l'auteur) ou série en chapitres.
 * Visible publiquement si la fiche est publiée et validée (scope global de HasPublication)
 * ET qu'au moins un chapitre est visible (scope withVisibleChapters()).
 */
class Comic extends Model
{
    use HasLikes, HasPublication, SoftDeletes;

    public const ONE_SHOT = 'one_shot';
    public const SERIES = 'serie';

    public const TYPES = [self::ONE_SHOT => 'One-shot', self::SERIES => 'Série'];
    public const FORMATS = ['vertical' => 'Défilement vertical (webtoon)', 'pages' => 'Page par page (BD, manga)'];
    public const DIRECTIONS = ['ltr' => 'Gauche → droite', 'rtl' => 'Droite → gauche (manga)'];
    public const SERIES_STATUSES = ['en_cours' => 'En cours', 'terminee' => 'Terminée', 'en_pause' => 'En pause'];

    public const MAX_TAGS = 8;

    protected $fillable = [
        'user_id', 'type', 'titre', 'slug', 'resume', 'couverture', 'genre', 'format_lecture', 'sens_lecture',
        'statut_serie', 'contenu_sensible', 'tags', 'published_at', 'moderation_status', 'moderation_note',
        'moderated_at', 'rights_certified_at', 'last_chapter_at', 'couverture_octets',
    ];

    protected function casts(): array
    {
        return [
            'contenu_sensible' => 'boolean',
            'tags' => 'array',
            'last_chapter_at' => 'datetime',
        ];
    }

    protected function asJson($value, $flags = 0)
    {
        return parent::asJson($value, $flags | JSON_UNESCAPED_UNICODE);
    }

    protected static function booted(): void
    {
        static::creating(function (Comic $comic) {
            $comic->slug ??= static::uniqueSlug($comic->titre);
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'recit';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->withoutGlobalScope('published')->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------- Relations ----------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function authorUser(): ?User
    {
        return $this->user;
    }

    /** Studio public de l'auteur (profil illustrateur), s'il existe. */
    public function artist()
    {
        return $this->hasOne(Artist::class, 'user_id', 'user_id');
    }

    /** Chapitres visibles publiquement, dans l'ordre de lecture. */
    public function chapters()
    {
        return $this->hasMany(ComicChapter::class)->orderBy('numero');
    }

    /** Tous les chapitres, brouillons et programmés compris (espace de l'auteur, admin). */
    public function allChapters()
    {
        return $this->hasMany(ComicChapter::class)->withoutGlobalScope('published')->orderBy('numero');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'comic_follows')->withTimestamps();
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // ---------- Requêtes ----------

    public function scopeWithVisibleChapters($query)
    {
        return $query->whereHas('chapters');
    }

    // ---------- Aides ----------

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    public function isOneShot(): bool
    {
        return $this->type === self::ONE_SHOT;
    }

    public function isRightToLeft(): bool
    {
        return $this->sens_lecture === 'rtl';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function seriesStatusLabel(): ?string
    {
        return $this->isOneShot() ? null : (self::SERIES_STATUSES[$this->statut_serie] ?? null);
    }

    public function genreLabel(): ?string
    {
        return $this->genre ? Genre::labelFor($this->genre) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->couverture ? asset('storage/'.$this->couverture) : null;
    }

    public function hasNewChapter(): bool
    {
        return $this->last_chapter_at !== null && $this->last_chapter_at->gte(now()->subDays(7));
    }

    public static function normalizeTags(?string $input): array
    {
        return Illustration::normalizeTags($input);
    }

    /** L'auteur et l'équipe voient les chapitres non publiés (aperçu depuis le studio). */
    public function canBePreviewedBy(?User $user): bool
    {
        return $this->isOwnedBy($user) || (bool) $user?->isStaff();
    }

    /** Étiquette du fil de l'accueil et des cartes : WEBTOON (vertical), MANGA (droite → gauche), sinon BD. */
    public function readingKind(): string
    {
        return match (true) {
            $this->format_lecture === 'vertical' => 'WEBTOON',
            $this->isRightToLeft() => 'MANGA',
            default => 'BD',
        };
    }

    /** « title » générique (écrans de modération et signalements, journal) : le titre du récit. */
    public function getTitleAttribute(): string
    {
        return (string) $this->titre;
    }
}
