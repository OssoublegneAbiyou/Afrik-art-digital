<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rôle d'administration, distinct du rôle métier (account_type : illustrateur, écrivain, visiteur).
            $table->string('role', 16)->default('membre')->after('is_admin');
            // Compte désactivé par un admin : connexion refusée, profil et œuvres masqués.
            $table->timestamp('deactivated_at')->nullable()->after('role');
            $table->index('role');
        });

        // Les admins actuels gardent leurs droits.
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 32);
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 16);
            $table->timestamps();

            $table->index('group');
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->string('cible_type', 64)->nullable();
            $table->string('cible_id', 64)->nullable();
            $table->json('avant')->nullable();
            $table->json('apres')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cible_type', 'cible_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('settings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'deactivated_at']);
        });
    }
};
