<?php

namespace App\Models;

use App\Models\Concerns\HasLikes;
use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Animation courte d'un illustrateur : « boucle » (muette, lecture automatique quand visible)
 * ou « clip » (avec son, lecture au clic). Visible publiquement si publiée, validée (HasPublication)
 * ET convertie (statut_traitement « pret » : scope ready()).
 */
class Animation extends Model
{
    use HasLikes, HasPublication, SoftDeletes;

    public const LOOP = 'boucle';
    public const CLIP = 'clip';
    public const MODES = [self::LOOP => 'Boucle (muette, comme un GIF)', self::CLIP => 'Clip (avec son)'];

    public const QUEUED = 'en_attente';
    public const PROCESSING = 'en_cours';
    public const READY = 'pret';
    public const FAILED = 'erreur';

    /** Disque des sources (non servi publiquement). */
    public const PRIVATE_DISK = 'local';

    protected $fillable = [
        'user_id', 'titre', 'slug', 'description', 'genre', 'tags', 'mode', 'contenu_sensible',
        'source', 'source_nom', 'mp4', 'webm', 'poster', 'poster_seconde', 'duree_s', 'largeur', 'hauteur',
        'taille_octets', 'statut_traitement', 'erreur', 'traite_le',
        'published_at', 'moderation_status', 'moderation_note', 'moderated_at', 'rights_certified_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'contenu_sensible' => 'boolean',
            'poster_seconde' => 'float',
            'duree_s' => 'float',
            'traite_le' => 'datetime',
        ];
    }

    protected function asJson($value, $flags = 0)
    {
        return parent::asJson($value, $flags | JSON_UNESCAPED_UNICODE);
    }

    protected static function booted(): void
    {
        static::creating(function (Animation $animation) {
            $animation->slug ??= static::uniqueSlug($animation->titre);
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'animation';
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

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // ---------- Requêtes ----------

    /** Animations converties et prêtes à être lues. */
    public function scopeReady($query)
    {
        return $query->where('statut_traitement', self::READY)->whereNotNull('mp4');
    }

    // ---------- Aides ----------

    /** Titre générique (modération, signalements, journal). */
    public function getTitleAttribute(): string
    {
        return (string) $this->titre;
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    public function isLoop(): bool
    {
        return $this->mode === self::LOOP;
    }

    public function isReady(): bool
    {
        return $this->statut_traitement === self::READY && $this->mp4 !== null;
    }

    /**
     * Visible par tout le monde : publiée, validée ET convertie. Remplace la version de HasPublication,
     * ce qui protège aussi la liaison de route (aperçu réservé à l'auteur et à l'équipe) et le comptage des vues.
     */
    public function isVisible(): bool
    {
        return $this->isPublished() && $this->isApproved() && $this->isReady();
    }

    /** L'auteur et l'équipe voient l'aperçu d'une animation non visible (brouillon, en attente, refusée, non prête). */
    public function canBePreviewedBy(?User $user): bool
    {
        return $this->isOwnedBy($user) || (bool) $user?->isStaff();
    }

    /** Animations listées publiquement : visibles (scope global « published ») et prêtes, auteur existant. */
    public function scopeListed($query)
    {
        return $query->ready()->whereHas('user');
    }

    /** Rapport largeur / hauteur pour réserver la place du lecteur (16:9 par défaut). */
    public function aspectRatio(): string
    {
        return $this->largeur && $this->hauteur ? $this->largeur.' / '.$this->hauteur : '16 / 9';
    }

    public function modeLabel(): string
    {
        return $this->isLoop() ? 'Boucle' : 'Clip';
    }

    public function genreLabel(): ?string
    {
        return $this->genre ? Genre::labelFor($this->genre) : null;
    }

    public function mp4Url(): ?string
    {
        return $this->mp4 ? asset('storage/'.$this->mp4) : null;
    }

    public function webmUrl(): ?string
    {
        return $this->webm ? asset('storage/'.$this->webm) : null;
    }

    public function posterUrl(): ?string
    {
        return $this->poster ? asset('storage/'.$this->poster) : null;
    }

    /** « 0:42 », « 1:05 ». */
    public function durationLabel(): ?string
    {
        if ($this->duree_s === null) {
            return null;
        }

        $seconds = (int) round($this->duree_s);

        return intdiv($seconds, 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
    }

    public static function normalizeTags(?string $input): array
    {
        return Illustration::normalizeTags($input);
    }
}
