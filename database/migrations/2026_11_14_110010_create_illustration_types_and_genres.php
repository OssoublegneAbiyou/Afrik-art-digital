<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Listes gérées par l'admin : types d'illustration et genres littéraires (ciels « Songe »).
 * Les colonnes illustrations.type et documents.genre gardent le slug (pas de clé étrangère) :
 * un type ou un genre désactivé ne casse aucune œuvre existante.
 * Valeurs initiales = celles codées en dur jusqu'ici (Illustration::TYPES, Document::GENRES,
 * config/genres.php et composant genre-emblem), pour un site identique au déploiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('illustration_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nom', 60);
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nom', 60);
            $table->string('phrase_accroche', 200)->nullable();
            // Courte description du « royaume » (page Écrivains).
            $table->string('description', 160)->nullable();
            // Ciel : couleur #RRGGBB (dégradé calculé) ou dégradé linéaire de couleurs hexadécimales.
            $table->string('ciel_jour', 160);
            $table->string('encre_jour', 7);
            $table->string('ciel_nuit', 160);
            $table->string('encre_nuit', 7);
            $table->string('lueur_nuit', 7);
            // Emblème SVG (assaini à l'enregistrement et à l'affichage). Vide : livre ouvert générique.
            $table->text('emblem')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('illustration_types')->insert([
            ['slug' => 'croquis', 'nom' => 'Croquis', 'ordre' => 1, 'actif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'couleur', 'nom' => 'Couleur', 'ordre' => 2, 'actif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'couverture', 'nom' => 'Couverture', 'ordre' => 3, 'actif' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $svg = fn (string $inner) => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">'.$inner.'</svg>';

        $genres = [
            [
                'slug' => 'fantasy',
                'nom' => 'Fantasy',
                'phrase_accroche' => 'Des îles qui flottent, des royaumes à bâtir, de la magie au coin de la rue.',
                'description' => 'Châteaux suspendus, magie et royaumes inventés.',
                'ciel_jour' => 'linear-gradient(160deg,#F5D9FF,#B79CF0 50%,#6B52B8)',
                'encre_jour' => '#1E1640',
                'ciel_nuit' => 'linear-gradient(160deg,#2A1A5E,#1A0F3D 60%,#0E0828)',
                'encre_nuit' => '#F3E6FF',
                'lueur_nuit' => '#C9B8FF',
                'emblem' => $svg(
                    '<path d="M30 95 V55 h10 v-8 h6 v8 h8 V40 h-4 l10-14 10 14 h-4 v15 h8 v-8 h6 v8 h10 v40 Z"/>'
                    .'<path d="M54 95 v-12 a6 6 0 0 1 12 0 v12"/>'
                    .'<path d="M60 26 V12 l11 4 -11 4"/>'
                    .'<path d="M20 95 h80 l-14 12 h-52 Z"/>'
                    .'<path d="M48 112 l-2 6 M72 112 l2 6"/>'
                ),
            ],
            [
                'slug' => 'science-fiction',
                'nom' => 'Science-fiction',
                'phrase_accroche' => 'Abidjan en 2099, des planètes à anneaux et des machines qui rêvent.',
                'description' => 'Futurs possibles, planètes lointaines et machines.',
                'ciel_jour' => 'linear-gradient(160deg,#0E1A3A,#1F3C7A 55%,#3FA7C9)',
                'encre_jour' => '#E6F4FF',
                'ciel_nuit' => 'linear-gradient(160deg,#030B1F,#0A1D45 60%,#06263A)',
                'encre_nuit' => '#D8F3FF',
                'lueur_nuit' => '#7FE3FF',
                'emblem' => $svg(
                    '<circle cx="60" cy="62" r="22"/>'
                    .'<ellipse cx="60" cy="62" rx="44" ry="11" transform="rotate(-18 60 62)"/>'
                    .'<path d="M46 50 q8-6 18-4"/>'
                    .'<circle cx="100" cy="24" r="5"/>'
                    .'<path d="M20 24 l2 6 6 2 -6 2 -2 6 -2-6 -6-2 6-2z"/>'
                    .'<path d="M96 98 l1.5 4 4 1.5 -4 1.5 -1.5 4 -1.5-4 -4-1.5 4-1.5z"/>'
                ),
            ],
            [
                'slug' => 'thriller',
                'nom' => 'Thriller',
                'phrase_accroche' => 'Sous le réverbère, une ombre. Chaque page cache un indice.',
                'description' => 'Enquêtes, suspense et ruelles sous le réverbère.',
                'ciel_jour' => 'linear-gradient(160deg,#120E1F,#2B1830 55%,#6A2A2A)',
                'encre_jour' => '#FFE3D4',
                'ciel_nuit' => 'linear-gradient(160deg,#07050D,#1A0D1C 60%,#2E1014)',
                'encre_nuit' => '#FFD9C7',
                'lueur_nuit' => '#FFB38A',
                'emblem' => $svg(
                    '<path d="M36 106 V40 q0-14 16-14 h8"/>'
                    .'<path d="M58 26 h18 l-5 11 h-8 Z"/>'
                    .'<path d="M63 37 L48 84 M71 37 L90 84" stroke-dasharray="2 6"/>'
                    .'<path d="M28 106 h16"/>'
                    .'<circle cx="84" cy="90" r="10"/>'
                    .'<path d="M91 97 l11 11"/>'
                ),
            ],
            [
                'slug' => 'conte',
                'nom' => 'Conte',
                'phrase_accroche' => 'Sous le baobab, les anciens racontent. Il était une fois…',
                'description' => 'Récits transmis sous le baobab, d’hier à demain.',
                'ciel_jour' => 'linear-gradient(160deg,#FFF3D6,#BDEBDD 55%,#6FC2AE)',
                'encre_jour' => '#123A33',
                'ciel_nuit' => 'linear-gradient(160deg,#0B1F1C,#103530 60%,#0A2420)',
                'encre_nuit' => '#DDF7EE',
                'lueur_nuit' => '#8FF0CF',
                'emblem' => $svg(
                    '<path d="M52 104 C54 88 52 72 50 62 h20 C68 72 66 88 68 104"/>'
                    .'<path d="M52 62 C44 54 36 52 30 44 M58 62 C58 50 56 42 52 34 M64 62 C66 50 72 44 76 36 M68 62 C78 56 86 54 92 46"/>'
                    .'<circle cx="27" cy="40" r="7"/><circle cx="50" cy="29" r="7"/>'
                    .'<circle cx="78" cy="31" r="7"/><circle cx="95" cy="42" r="7"/>'
                    .'<path d="M26 104 h68"/>'
                ),
            ],
            [
                'slug' => 'poesie',
                'nom' => 'Poésie',
                'phrase_accroche' => 'Une plume, un peu d’encre, et les mots qui tombent comme des pétales.',
                'description' => 'Vers, slams et mots qui tombent comme des pétales.',
                'ciel_jour' => 'linear-gradient(160deg,#FFE3EC,#FFC9A8 55%,#F29A7E)',
                'encre_jour' => '#4A1F16',
                'ciel_nuit' => 'linear-gradient(160deg,#24101C,#3A1526 60%,#1E0B14)',
                'encre_nuit' => '#FFDCE6',
                'lueur_nuit' => '#FF9FBF',
                'emblem' => $svg(
                    '<path d="M88 14 C60 26 44 52 38 86 C60 76 80 52 88 14 Z"/>'
                    .'<path d="M38 86 L82 26"/>'
                    .'<path d="M38 86 L31 100"/>'
                    .'<circle cx="29" cy="108" r="3"/>'
                    .'<path d="M50 100 h34 M58 108 h22"/>'
                ),
            ],
        ];

        foreach ($genres as $index => $genre) {
            DB::table('genres')->insert($genre + [
                'ordre' => $index + 1,
                'actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
        Schema::dropIfExists('illustration_types');
    }
};
