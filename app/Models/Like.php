<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * « J'aime » d'un membre sur une illustration ou un texte (un seul par personne et par œuvre).
 */
class Like extends Model
{
    protected $fillable = ['user_id'];

    public function likeable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
