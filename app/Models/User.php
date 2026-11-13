<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        // google_id, google_email et avatar_url ne sont écrits que par GoogleAuthController (forceFill).
        'account_type',
        'account_type_selected',
        'is_admin',
        'role',
        'deactivated_at',
        'password',
    ];

    // Rôles d'administration (distincts du rôle métier account_type).
    public const ROLE_MEMBER = 'membre';
    public const ROLE_MODERATOR = 'moderateur';
    public const ROLE_ADMIN = 'admin';

    public const ROLES = [
        self::ROLE_MEMBER => 'Membre',
        self::ROLE_MODERATOR => 'Modérateur',
        self::ROLE_ADMIN => 'Admin',
    ];

    protected static function booted(): void
    {
        // « role » est la référence ; « is_admin » reste synchronisé pour le code et les données existants.
        static::saving(function (User $user) {
            if ($user->isDirty('role') && $user->role) {
                // Rôle choisi explicitement : is_admin en découle.
                $user->is_admin = $user->role === self::ROLE_ADMIN;
            } elseif ($user->isDirty('is_admin') || ! $user->role) {
                // Ancien code (is_admin seul) : on en déduit le rôle ; un admin qui perd is_admin redevient membre.
                $user->role = $user->is_admin
                    ? self::ROLE_ADMIN
                    : (($user->role ?? self::ROLE_MEMBER) === self::ROLE_ADMIN ? self::ROLE_MEMBER : ($user->role ?? self::ROLE_MEMBER));
                $user->is_admin = $user->role === self::ROLE_ADMIN;
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'account_type_selected' => 'boolean',
            'is_admin' => 'boolean',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function artist()
    {
        return $this->hasOne(Artist::class);
    }

    public function writer()
    {
        return $this->hasOne(Writer::class);
    }

    public function isArtist(): bool
    {
        return $this->account_type === 'artist';
    }

    public function isWriter(): bool
    {
        return $this->account_type === 'writer';
    }

    public function isVisitor(): bool
    {
        return $this->account_type === 'visitor';
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || ($this->role === null && (bool) $this->is_admin);
    }

    /**
     * Modérateur ou admin : valide les œuvres, traite signalements, demandes et mises en avant.
     */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->role === self::ROLE_MODERATOR;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? self::ROLES[self::ROLE_MEMBER];
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /** Compte relié à un compte Google (« Continuer avec Google »). */
    public function hasGoogle(): bool
    {
        return filled($this->google_id);
    }

    /** Compte sans mot de passe (créé avec Google) : il se connecte uniquement avec Google. */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    /**
     * Photo affichée (profil, fiches) : il n'existe pas encore d'avatar propre au membre,
     * c'est donc la photo Google (avatar_url), remplie seulement si elle était vide.
     */
    public function avatarUrl(): ?string
    {
        return filled($this->avatar_url) ? $this->avatar_url : null;
    }

    public function storageLimitBytes(): int
    {
        return max(1, (int) setting('regles.stockage_max_go', 1)) * 1024 * 1024 * 1024;
    }

    public function followedArtists()
    {
        return $this->belongsToMany(Artist::class, 'artist_follows')->withTimestamps();
    }

    public function favoriteIllustrations()
    {
        return $this->belongsToMany(Illustration::class, 'illustration_favorites')->withTimestamps();
    }

    public function bookmarkedDocuments()
    {
        return $this->belongsToMany(Document::class, 'document_bookmarks')->withTimestamps();
    }

    public function storageUsedBytes(): int
    {
        $artistStorage = $this->artist
            ? $this->artist->allIllustrations->sum(function (Illustration $illustration) {
                if ($illustration->file_size_bytes) {
                    return (int) $illustration->file_size_bytes;
                }

                return Storage::disk('public')->exists($illustration->image_path)
                    ? Storage::disk('public')->size($illustration->image_path)
                    : 0;
            })
            : 0;

        $artistStorage += $this->artist ? (int) $this->artist->banner_size_bytes : 0;

        $writerStorage = $this->writer
            ? $this->writer->allDocuments->sum(function (Document $document) {
                return (int) $document->file_size_bytes + (int) $document->cover_image_size_bytes;
            })
            : 0;

        $writerStorage += $this->writer ? (int) $this->writer->banner_size_bytes : 0;

        return $artistStorage + $writerStorage;
    }
}
