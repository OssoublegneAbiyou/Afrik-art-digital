<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pages éditables (mentions légales, règles de publication, confidentialité) et annonces en bandeau.
 * « {nom} » dans le contenu est remplacé à l'affichage par le nom du collectif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titre');
            $table->longText('contenu')->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->boolean('publie')->default(true);
            $table->timestamps();
        });

        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
            $table->string('texte', 140);
            $table->string('lien')->nullable();
            $table->dateTime('debut')->nullable();
            $table->dateTime('fin')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('pages')->insert([
            [
                'slug' => 'mentions-legales',
                'titre' => 'Mentions légales',
                'meta_description' => 'Mentions légales du site {nom} : éditeur, propriété intellectuelle et données personnelles.',
                'contenu' => <<<'HTML'
<h2>Éditeur du site</h2>
<p>{nom}, collectif d'illustrateurs et d'écrivains basé à Abidjan.</p>
<h2>Propriété intellectuelle</h2>
<p>Les illustrations, textes et portfolios publiés sur ce site appartiennent à leurs auteurs respectifs. Toute reproduction ou réutilisation sans l'accord de l'auteur est interdite.</p>
<h2>Données personnelles</h2>
<p>Les comptes sont créés par l'administrateur du collectif. Les informations saisies (nom, adresse e-mail, biographie, liens) servent uniquement au fonctionnement du site. Pour les consulter, les corriger ou les supprimer, contactez le collectif.</p>
<h2>Stockage local</h2>
<p>Le site mémorise dans votre navigateur vos préférences d'affichage (mode jour ou nuit, ambiance et taille du texte en lecture). Aucune donnée n'est transmise à des tiers.</p>
HTML,
                'publie' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'regles-de-publication',
                'titre' => 'Règles de publication',
                'meta_description' => 'Ce que les membres de {nom} peuvent publier : œuvres originales, droits, contenus interdits, validation et signalement.',
                'contenu' => <<<'HTML'
<p>{nom} est la vitrine d'un collectif : chaque membre est responsable de ce qu'il publie, et le collectif de ce qu'il montre. Ces règles s'appliquent aux illustrations, portfolios, récits et poèmes.</p>
<h2>Des œuvres originales</h2>
<p>Publiez uniquement des œuvres dont vous êtes l'auteur. Les collaborations sont bienvenues si chaque participant est d'accord et crédité.</p>
<h2>Les droits</h2>
<ul>
<li>Pas de copie, ni de calque, ni de reprise d'une œuvre existante présentée comme la vôtre.</li>
<li>Pas de personnages, logos, marques ou visuels appartenant à d'autres (studios, éditeurs, entreprises) sans autorisation écrite.</li>
<li>Vous gardez tous vos droits : publier sur le site n'en cède aucun au collectif.</li>
</ul>
<h2>Contenus interdits</h2>
<ul>
<li>Contenus haineux, discriminatoires, harcelants ou appelant à la violence.</li>
<li>Contenus sexuels explicites, et tout contenu sexualisant des mineurs.</li>
<li>Informations personnelles d'autrui publiées sans son accord.</li>
<li>Publicité, spam ou liens trompeurs.</li>
</ul>
<h2>Validation par l'équipe</h2>
<p>Lorsque la validation avant publication est activée, chaque nouvelle œuvre est relue par l'équipe avant d'apparaître sur le site. En cas de refus, le motif vous est indiqué : vous pouvez corriger l'œuvre et la soumettre à nouveau.</p>
<h2>Signalement</h2>
<p>Chaque œuvre comporte un bouton « Signaler ». L'équipe examine chaque signalement et peut masquer une œuvre qui ne respecte pas ces règles ; son auteur en est alors prévenu avec le motif.</p>
HTML,
                'publie' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'confidentialite',
                'titre' => 'Confidentialité',
                'meta_description' => 'Données collectées par {nom}, usage, conservation et droits des personnes.',
                'contenu' => <<<'HTML'
<p>{nom} collecte le minimum de données nécessaire au fonctionnement du site. Elles ne sont ni vendues ni transmises à des tiers.</p>
<h2>Données collectées</h2>
<ul>
<li><strong>Compte</strong> : nom, adresse e-mail, mot de passe (chiffré), biographie, liens et œuvres publiées par les membres.</li>
<li><strong>Adresse e-mail</strong> : pour la connexion, les notifications du site et, si vous le souhaitez, la réponse à un signalement.</li>
<li><strong>Demandes d'adhésion</strong> : nom, e-mail, discipline, lien vers le portfolio et message, conservés le temps de traiter la demande.</li>
<li><strong>Statistiques de vue</strong> : un compteur anonyme du nombre de vues des œuvres, sans profil de visiteur.</li>
<li><strong>Cookies techniques</strong> : session et sécurité des formulaires, ainsi que vos préférences d'affichage stockées dans votre navigateur. Aucun cookie publicitaire ni de mesure d'audience tierce.</li>
</ul>
<h2>Vos droits</h2>
<p>Vous pouvez consulter, corriger ou supprimer vos données à tout moment : depuis votre espace pour le profil et les œuvres, ou en contactant le collectif pour le reste, y compris la suppression complète de votre compte.</p>
<h2>Contact</h2>
<p>Pour toute question sur vos données, contactez le collectif : ses coordonnées figurent sur la page À propos.</p>
HTML,
                'publie' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('annonces');
        Schema::dropIfExists('pages');
    }
};
