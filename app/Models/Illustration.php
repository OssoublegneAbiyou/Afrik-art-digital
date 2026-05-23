<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Illustration extends Model
{
    protected $fillable = [
        'artist_id',
        'title',
        'image_path',
        'file_size_bytes',
    ];

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
}
