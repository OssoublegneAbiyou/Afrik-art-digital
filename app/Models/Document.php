<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'writer_id',
        'title',
        'description',
        'file_path',
        'file_type',
        'file_size_bytes',
        'cover_image_path',
        'cover_image_size_bytes',
    ];

    public function writer()
    {
        return $this->belongsTo(Writer::class);
    }

    public function bookmarkedBy()
    {
        return $this->belongsToMany(User::class, 'document_bookmarks')->withTimestamps();
    }
}
