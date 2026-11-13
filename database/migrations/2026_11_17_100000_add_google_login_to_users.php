<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * « Continuer avec Google » :
 * - google_id devient unique (un compte Google = un compte du site) ;
 * - google_email : l'adresse Google reliée (affichée dans « Mon compte ») ;
 * - avatar_url remplace l'ancienne colonne google_avatar (valeurs recopiées) ;
 * - password devient nullable (un compte créé avec Google n'a pas de mot de passe).
 * Les jetons d'accès Google ne sont jamais stockés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_email')->nullable()->after('google_id');
            $table->string('avatar_url', 1024)->nullable()->after('google_email');
        });

        if (Schema::hasColumn('users', 'google_avatar')) {
            DB::table('users')->whereNotNull('google_avatar')->update(['avatar_url' => DB::raw('google_avatar')]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('google_avatar');
            });
        }

        // Comptes Google de l'ancienne version : leur adresse Google était celle du compte.
        DB::table('users')->whereNotNull('google_id')->whereNull('google_email')->update(['google_email' => DB::raw('email')]);

        Schema::table('users', function (Blueprint $table) {
            $table->unique('google_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        // password reste nullable : le remettre obligatoire échouerait pour les comptes créés avec Google.
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_avatar')->nullable()->after('google_id');
        });

        DB::table('users')->whereNotNull('avatar_url')->update(['google_avatar' => DB::raw('avatar_url')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_email', 'avatar_url']);
        });
    }
};
