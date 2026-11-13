<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Texte écrit ou collé dans l'éditeur du site : prioritaire pour la lecture.
            $table->longText('content')->nullable()->after('genre');
            $table->boolean('allow_download')->default(true)->after('cover_image_size_bytes');
            $table->unsignedInteger('download_count')->default(0)->after('allow_download');
            // Disque du fichier : « public » pour les anciens envois, « local » (privé) pour les nouveaux.
            $table->string('file_disk', 16)->default('public')->after('file_path');
        });

        // Une œuvre peut désormais exister sans fichier (texte écrit directement sur le site).
        Schema::table('documents', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('file_type', 10)->nullable()->change();
            $table->unsignedBigInteger('file_size_bytes')->default(0)->change();
        });

        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->decimal('progress', 5, 4)->default(0);
            $table->unsignedSmallInteger('chapter')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_progress');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['content', 'allow_download', 'download_count', 'file_disk']);
        });
    }
};
