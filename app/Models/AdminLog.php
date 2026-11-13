<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal des modifications faites depuis l'administration (lecture seule pour les admins).
 * Écrire via App\Support\AdminLogger::log().
 */
class AdminLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'cible_type', 'cible_id', 'avant', 'apres'];

    protected function casts(): array
    {
        return [
            'avant' => 'array',
            'apres' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
