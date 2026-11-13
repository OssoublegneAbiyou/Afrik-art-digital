<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['illustrations', 'documents'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // « approved » : visible ; « pending » : en attente de validation ; « rejected » : refusée ou retirée.
                // Tout ce qui est déjà en ligne reste « approved ».
                $table->string('moderation_status', 16)->default('approved')->after('published_at');
                $table->text('moderation_note')->nullable()->after('moderation_status');
                $table->timestamp('moderated_at')->nullable()->after('moderation_note');
                // Date à laquelle l'auteur a certifié détenir les droits de l'œuvre.
                $table->timestamp('rights_certified_at')->nullable()->after('moderated_at');
                $table->index('moderation_status');
            });
        }

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('reportable_type', 32);
            $table->unsignedBigInteger('reportable_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 32);
            $table->text('message')->nullable();
            $table->string('email')->nullable();
            // « open », « dismissed » (classé sans suite) ou « actioned » (œuvre retirée).
            $table->string('status', 16)->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');

        foreach (['illustrations', 'documents'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['moderation_status']);
                $table->dropColumn(['moderation_status', 'moderation_note', 'moderated_at', 'rights_certified_at']);
            });
        }
    }
};
