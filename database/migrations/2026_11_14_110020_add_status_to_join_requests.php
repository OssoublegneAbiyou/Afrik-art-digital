<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Circuit complet des demandes d'adhésion : statut, qui l'a traitée et quand, note interne,
 * motif de refus, compte créé ou lié.
 *
 * Reprise des données : l'ancienne admin n'avait qu'un « traitée » (handled_at) sans dire si la
 * demande avait été acceptée. On ne passe en « acceptee » que les demandes traitées dont l'adresse
 * correspond à un compte existant (le compte a donc bien été créé). Les autres demandes traitées
 * restent « nouvelle » — on ne sait pas si elles ont été refusées — avec une note interne qui le
 * signale : l'équipe tranche elle-même. La colonne handled_at est conservée (historique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('join_requests', function (Blueprint $table) {
            $table->string('statut', 16)->default('nouvelle')->index()->after('message');
            $table->foreignId('traite_par')->nullable()->after('statut')->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable()->after('traite_par');
            $table->text('note_interne')->nullable()->after('traite_le');
            $table->text('motif_refus')->nullable()->after('note_interne');
            $table->foreignId('user_id')->nullable()->after('motif_refus')->constrained('users')->nullOnDelete();
        });

        DB::table('join_requests')->whereNotNull('handled_at')->orderBy('id')->each(function ($request) {
            $userId = DB::table('users')->where('email', strtolower(trim($request->email)))->value('id');

            DB::table('join_requests')->where('id', $request->id)->update($userId
                ? ['statut' => 'acceptee', 'traite_le' => $request->handled_at, 'user_id' => $userId]
                : ['note_interne' => 'Marquée comme traitée dans l’ancienne administration le '
                    .date('d/m/Y', strtotime($request->handled_at)).', sans compte créé : à confirmer (accepter ou refuser).']);
        });
    }

    public function down(): void
    {
        Schema::table('join_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('traite_par');
            $table->dropConstrainedForeignId('user_id');
            $table->dropIndex(['statut']);
            $table->dropColumn(['statut', 'traite_le', 'note_interne', 'motif_refus']);
        });
    }
};
