<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedSelection extends Model
{
    protected $fillable = [
        'featured_for',
        'artist_id',
        'writer_id',
    ];

    protected function casts(): array
    {
        return [
            'featured_for' => 'date',
        ];
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function writer()
    {
        return $this->belongsTo(Writer::class);
    }
}
