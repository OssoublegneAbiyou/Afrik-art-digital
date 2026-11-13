<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Planche d'un chapitre. L'original reste sur le disque privé ; le lecteur et les grilles n'utilisent que
 * les versions WebP converties (lecture, miniature, tranches pour les images très hautes).
 */
class ComicPage extends Model
{
    public const PENDING = 'en_attente';
    public const READY = 'pret';
    public const FAILED = 'erreur';

    /** Disque des originaux (non servi publiquement). */
    public const PRIVATE_DISK = 'local';

    protected $fillable = [
        'comic_chapter_id', 'ordre', 'nom_fichier', 'original', 'lecture', 'miniature', 'tranches', 'largeur',
        'hauteur', 'taille_octets', 'alt', 'double_page', 'statut_traitement', 'erreur', 'video', 'duree_s',
    ];

    protected function casts(): array
    {
        return [
            'tranches' => 'array',
            'double_page' => 'boolean',
            'duree_s' => 'float',
        ];
    }

    /** Page animée (§14) : boucle MP4 muette ; « lecture » en est le poster. */
    public function isAnimated(): bool
    {
        return $this->video !== null;
    }

    public function videoUrl(): ?string
    {
        return $this->video ? asset('storage/'.$this->video) : null;
    }

    public function chapter()
    {
        return $this->belongsTo(ComicChapter::class, 'comic_chapter_id')->withoutGlobalScope('published');
    }

    public function isReady(): bool
    {
        return $this->statut_traitement === self::READY;
    }

    public function readingUrl(): ?string
    {
        return $this->lecture ? asset('storage/'.$this->lecture) : null;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->miniature ? asset('storage/'.$this->miniature) : null;
    }
}
