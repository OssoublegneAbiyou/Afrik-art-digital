<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Récits dessinés : one-shots et séries (manga, BD, webtoon).
 * Publication et validation : mêmes colonnes que les illustrations (trait HasPublication).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16)->default('one_shot');            // one_shot | serie
            $table->string('titre', 120);
            $table->string('slug')->unique();
            $table->text('resume')->nullable();
            $table->string('couverture')->nullable();                   // disque public
            $table->string('genre', 64)->nullable();                    // slug de la table genres
            $table->string('format_lecture', 16)->default('vertical');  // vertical | pages
            $table->string('sens_lecture', 3)->default('ltr');          // ltr | rtl (manga)
            $table->string('statut_serie', 16)->default('en_cours');    // en_cours | terminee | en_pause
            $table->boolean('contenu_sensible')->default(false);
            $table->json('tags')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            // Date de sortie du dernier chapitre visible (badge « Nouveau chapitre », tri « Mis à jour »).
            $table->timestamp('last_chapter_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('moderation_status', 16)->default('approved')->index();
            $table->text('moderation_note')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamp('rights_certified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('comic_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->constrained()->cascadeOnDelete();
            $table->decimal('numero', 5, 1);
            $table->string('titre', 120)->nullable();
            $table->text('note_auteur')->nullable();
            $table->unsignedInteger('pages_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            // Abonnés prévenus de la sortie (évite les doublons : publication immédiate, validée ou programmée).
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('moderation_status', 16)->default('approved')->index();
            $table->text('moderation_note')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamp('rights_certified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['comic_id', 'numero']);
        });

        Schema::create('comic_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('ordre');
            $table->string('nom_fichier')->nullable();       // nom d'origine (tri naturel)
            $table->string('original');                      // disque privé, jamais servi
            $table->string('lecture')->nullable();           // WebP lecteur (disque public)
            $table->string('miniature')->nullable();         // WebP 300 px
            // Image très haute (webtoon) découpée : [{chemin, largeur, hauteur}, …] dans l'ordre ; null sinon.
            $table->json('tranches')->nullable();
            $table->unsignedInteger('largeur')->nullable();
            $table->unsignedInteger('hauteur')->nullable();
            $table->unsignedBigInteger('taille_octets')->default(0);   // original + versions (quota)
            $table->string('alt')->nullable();
            $table->boolean('double_page')->default(false);
            $table->string('statut_traitement', 16)->default('en_attente'); // en_attente | pret | erreur
            $table->string('erreur')->nullable();
            $table->timestamps();
            $table->index(['comic_chapter_id', 'ordre']);
        });

        Schema::create('comic_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comic_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'comic_id']);
        });

        Schema::create('comic_reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comic_chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_ordre')->default(1);
            $table->unsignedTinyInteger('pourcentage')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'comic_chapter_id']);
        });

        // Favoris génériques (morph map : « comic »…). Les favoris d'illustrations existants restent dans
        // illustration_favorites.
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('favoritable_type', 32);
            $table->unsignedBigInteger('favoritable_id');
            $table->timestamps();
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
            $table->index(['favoritable_type', 'favoritable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('comic_reading_progress');
        Schema::dropIfExists('comic_follows');
        Schema::dropIfExists('comic_pages');
        Schema::dropIfExists('comic_chapters');
        Schema::dropIfExists('comics');
    }
};
