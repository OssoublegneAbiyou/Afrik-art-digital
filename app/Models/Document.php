<?php

namespace App\Models;

use App\Models\Concerns\HasLikes;
use App\Models\Concerns\HasPublication;
use App\Support\ImageThumbnail;
use App\Support\PlatformRules;
use App\Support\ReaderText;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasLikes;
    use HasPublication;

    // Les genres (slug dans « genre ») viennent de la table genres, gérée dans l'admin : voir Genre.

    // Nouveaux manuscrits : disque privé, servis uniquement par les routes de lecture et de téléchargement.
    public const PRIVATE_DISK = 'local';

    protected $fillable = [
        'writer_id',
        'title',
        'description',
        'genre',
        'content',
        'file_path',
        'file_disk',
        'file_type',
        'file_size_bytes',
        'cover_image_path',
        'cover_image_size_bytes',
        'allow_download',
        'published_at',
        'moderation_status',
        'moderation_note',
        'moderated_at',
        'rights_certified_at',
    ];

    protected function casts(): array
    {
        return [
            'allow_download' => 'boolean',
            'download_count' => 'integer',
            'view_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Document $document) {
            ImageThumbnail::generate($document->cover_image_path);
        });

        static::deleted(function (Document $document) {
            ImageThumbnail::delete($document->cover_image_path);
        });
    }

    public function writer()
    {
        return $this->belongsTo(Writer::class);
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function bookmarkedBy()
    {
        return $this->belongsToMany(User::class, 'document_bookmarks')->withTimestamps();
    }

    public function genreLabel(): ?string
    {
        // Un genre désactivé garde son libellé sur les récits existants.
        return Genre::labelFor($this->genre);
    }

    /**
     * Slug du genre s'il existe dans la table (actif ou non), sinon null (ciel et emblème par défaut).
     */
    public function knownGenre(): ?string
    {
        return Genre::findBySlug($this->genre) ? $this->genre : null;
    }

    public function coverUrl(): string
    {
        return asset('storage/'.$this->cover_image_path);
    }

    /**
     * Couverture réduite (WebP ≈ 600 px) pour les étagères et vignettes.
     */
    public function coverThumbnailUrl(): string
    {
        return ImageThumbnail::url($this->cover_image_path, route('thumbnails.cover', $this));
    }

    public function authorUser(): ?User
    {
        return $this->writer?->user;
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->writer?->user_id === $user->id;
    }

    // ---------- Fichier joint ----------

    public function fileDisk(): Filesystem
    {
        return Storage::disk($this->file_disk ?: 'public');
    }

    public function hasFile(): bool
    {
        return filled($this->file_path) && $this->fileDisk()->exists($this->file_path);
    }

    public function deleteFiles(): void
    {
        if (filled($this->file_path)) {
            $this->fileDisk()->delete($this->file_path);
        }

        if (filled($this->cover_image_path)) {
            Storage::disk('public')->delete($this->cover_image_path);
        }
    }

    public function isPdf(): bool
    {
        return strtolower((string) $this->file_type) === 'pdf';
    }

    /**
     * Ce qui peut être téléchargé : le fichier joint, ou à défaut le texte écrit dans l'éditeur (.txt).
     */
    public function isDownloadable(): bool
    {
        return $this->hasFile() || filled($this->content);
    }

    /**
     * Téléchargement ouvert aux lecteurs, selon la règle de la plateforme (regles.telechargement_recits) :
     * toujours, jamais, ou selon le choix de l'auteur (allow_download).
     */
    public function downloadOpenToReaders(): bool
    {
        return match (PlatformRules::downloadMode()) {
            PlatformRules::DOWNLOAD_ALLOWED => true,
            PlatformRules::DOWNLOAD_FORBIDDEN => false,
            default => (bool) $this->allow_download,
        };
    }

    /**
     * L'auteur et l'équipe peuvent toujours télécharger ; les autres selon la règle ci-dessus.
     */
    public function canBeDownloadedBy(?User $user): bool
    {
        return $this->downloadOpenToReaders() || $this->isOwnedBy($user) || (bool) $user?->isStaff();
    }

    public function downloadFileName(): string
    {
        $extension = $this->hasFile() ? strtolower((string) $this->file_type) : 'txt';

        return (Str::slug($this->title) ?: 'oeuvre').'.'.$extension;
    }

    // ---------- Texte lisible dans le site ----------

    /**
     * Texte affiché sur la page de lecture : celui de l'éditeur en priorité, sinon extrait du fichier TXT ou DOCX.
     * Un PDF sans version texte n'a pas de texte lisible (il s'affiche dans un cadre).
     */
    public function readableText(): ?string
    {
        if (filled($this->content)) {
            return $this->content;
        }

        if (! $this->hasFile()) {
            return null;
        }

        return match (strtolower((string) $this->file_type)) {
            'txt' => $this->fileDisk()->get($this->file_path),
            'docx' => ReaderText::fromDocx($this->fileDisk()->path($this->file_path)),
            default => null,
        };
    }

    /**
     * Texte à proposer dans l'éditeur quand l'auteur n'en a pas encore écrit (extraction TXT / DOCX).
     */
    public function extractableText(): ?string
    {
        return filled($this->content) ? $this->content : $this->readableText();
    }
}
