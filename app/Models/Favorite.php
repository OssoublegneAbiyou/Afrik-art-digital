<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Favori générique (récits dessinés…). Les favoris d'illustrations historiques restent dans illustration_favorites.
 */
class Favorite extends Model
{
    protected $fillable = ['user_id', 'favoritable_type', 'favoritable_id'];

    public function favoritable()
    {
        return $this->morphTo()->withoutGlobalScope('published');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
