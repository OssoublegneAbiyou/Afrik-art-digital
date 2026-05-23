<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtistPortfolio extends Model
{
    protected $fillable = [
        'artist_id',
        'title',
        'description',
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function items()
    {
        return $this->hasMany(ArtistPortfolioItem::class)->orderBy('position');
    }
}
