<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtistPortfolioItem extends Model
{
    protected $fillable = [
        'artist_portfolio_id',
        'illustration_id',
        'position',
        'theme',
        'music',
        'custom_music_path',
        'custom_music_size_bytes',
        'description',
        'guide_audio_path',
        'guide_audio_size_bytes',
    ];

    public function portfolio()
    {
        return $this->belongsTo(ArtistPortfolio::class, 'artist_portfolio_id');
    }

    public function illustration()
    {
        return $this->belongsTo(Illustration::class);
    }
}
