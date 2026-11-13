<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cases animées dans un chapitre (§14) : une planche peut être une courte boucle vidéo muette (≤ 15 s).
 * « video » = MP4 H.264 sans son (disque public) ; « lecture » garde le poster WebP et « miniature » sa miniature,
 * donc un lecteur qui ignore « video » affiche l'image fixe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comic_pages', function (Blueprint $table) {
            $table->string('video')->nullable()->after('miniature');
            $table->decimal('duree_s', 6, 2)->nullable()->after('video');
        });
    }

    public function down(): void
    {
        Schema::table('comic_pages', function (Blueprint $table) {
            $table->dropColumn(['video', 'duree_s']);
        });
    }
};
