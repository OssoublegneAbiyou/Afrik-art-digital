<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Espace artiste des récits dessinés :
 * - comics.couverture_octets : taille de la couverture, comptée dans le quota BD ;
 * - comic_chapters.retard_signale_at : l'auteur a été prévenu (une seule fois) que son chapitre programmé
 *   attend encore la validation de l'équipe à l'heure prévue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            $table->unsignedBigInteger('couverture_octets')->default(0)->after('couverture');
        });

        Schema::table('comic_chapters', function (Blueprint $table) {
            $table->timestamp('retard_signale_at')->nullable()->after('notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('comic_chapters', function (Blueprint $table) {
            $table->dropColumn('retard_signale_at');
        });

        Schema::table('comics', function (Blueprint $table) {
            $table->dropColumn('couverture_octets');
        });
    }
};
