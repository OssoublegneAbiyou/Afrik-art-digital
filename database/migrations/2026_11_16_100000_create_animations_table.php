<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Animations courtes : boucles muettes (comme un GIF) et clips avec son.
 * Publication et validation : mêmes colonnes que les autres œuvres (trait HasPublication).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titre', 120);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('genre', 64)->nullable();              // slug de la table genres
            $table->json('tags')->nullable();
            $table->string('mode', 8)->default('boucle');         // boucle | clip
            $table->boolean('contenu_sensible')->default(false);

            // Fichiers : source sur le disque privé ; versions converties sur le disque public (noms versionnés).
            $table->string('source');
            $table->string('source_nom')->nullable();             // nom d'origine
            $table->string('mp4')->nullable();
            $table->string('webm')->nullable();
            $table->string('poster')->nullable();                 // WebP
            $table->decimal('poster_seconde', 6, 2)->nullable();  // instant choisi par l'artiste (défaut : 1 s)
            $table->decimal('duree_s', 7, 2)->nullable();
            $table->unsignedInteger('largeur')->nullable();
            $table->unsignedInteger('hauteur')->nullable();
            $table->unsignedBigInteger('taille_octets')->default(0); // source + versions (quota)

            $table->string('statut_traitement', 16)->default('en_attente'); // en_attente | en_cours | pret | erreur
            $table->string('erreur')->nullable();
            $table->timestamp('traite_le')->nullable();

            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->string('moderation_status', 16)->default('approved')->index();
            $table->text('moderation_note')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamp('rights_certified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'statut_traitement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animations');
    }
};
