<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Writer extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'banner_path',
        'banner_size_bytes',
        'instagram',
        'facebook',
        'website',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
