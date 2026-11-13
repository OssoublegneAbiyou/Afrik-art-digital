<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Où un membre s'est arrêté dans un texte (pour « Reprendre la lecture »).
 */
class ReadingProgress extends Model
{
    protected $table = 'reading_progress';

    protected $fillable = ['user_id', 'document_id', 'progress', 'chapter'];

    protected function casts(): array
    {
        return [
            'progress' => 'float',
            'chapter' => 'integer',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function percent(): int
    {
        return (int) round($this->progress * 100);
    }
}
