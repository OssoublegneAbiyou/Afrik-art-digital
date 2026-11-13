<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une ligne de réglage du collectif. Lire et écrire via App\Support\Settings (cache, types, journal).
 */
class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];
}
