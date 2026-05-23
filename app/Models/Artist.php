<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'banner_path',
        'banner_size_bytes',
        'instagram',
        'twitter',
        'facebook',
        'youtube',
        'behance',
        'website'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Un artiste a plusieurs illustrations
    public function illustrations()
    {
        return $this->hasMany(Illustration::class);
    }

    public function portfolios()
    {
        return $this->hasMany(ArtistPortfolio::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'artist_follows')->withTimestamps();
    }
}
