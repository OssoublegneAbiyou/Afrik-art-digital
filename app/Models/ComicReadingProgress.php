<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Où un membre s'est arrêté dans un chapitre de récit dessiné (les visiteurs : localStorage).
 */
class ComicReadingProgress extends Model
{
    protected $table = 'comic_reading_progress';

    protected $fillable = ['user_id', 'comic_chapter_id', 'page_ordre', 'pourcentage'];

    public function chapter()
    {
        return $this->belongsTo(ComicChapter::class, 'comic_chapter_id')->withoutGlobalScope('published');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Chapitre lu en entier (≥ 90 %). */
    public function isComplete(): bool
    {
        return $this->pourcentage >= 90;
    }
}
