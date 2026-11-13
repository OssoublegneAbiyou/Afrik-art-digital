<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Page « Confidentialité » : ajoute le paragraphe « Connexion avec Google ».
 * - Contenu encore identique à l'original : la section est insérée avant « Vos droits ».
 * - Contenu modifié par l'équipe : le paragraphe est ajouté à la fin, sans rien remplacer.
 * - Paragraphe déjà présent : rien ne change.
 */
return new class extends Migration
{
    private const MARKER = 'Connexion avec Google';

    private const ORIGINAL = <<<'HTML'
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
HTML;

    private const TEXT = 'Si tu choisis de te connecter avec Google, nous recevons uniquement ton nom, ton adresse e-mail et ta photo de profil Google. Nous ne recevons ni ton mot de passe ni l’accès à tes autres données Google. Tu peux délier ton compte Google à tout moment depuis « Mon compte ».';

    public function up(): void
    {
        $page = DB::table('pages')->where('slug', 'confidentialite')->first();

        if ($page === null || str_contains((string) $page->contenu, self::MARKER)) {
            return;
        }

        $current = (string) $page->contenu;

        if ($this->normalize($current) === $this->normalize(self::ORIGINAL)) {
            $section = '<h2>'.self::MARKER.'</h2>'."\n".'<p>'.self::TEXT.'</p>'."\n";
            $content = str_replace('<h2>Vos droits</h2>', $section.'<h2>Vos droits</h2>', $current);
        } else {
            $content = rtrim($current)."\n".'<p><strong>'.self::MARKER.'</strong> — '.self::TEXT.'</p>';
        }

        DB::table('pages')->where('id', $page->id)->update(['contenu' => $content, 'updated_at' => now()]);
        Cache::forget('pages.footer');
    }

    public function down(): void
    {
        $page = DB::table('pages')->where('slug', 'confidentialite')->first();

        if ($page === null) {
            return;
        }

        $content = str_replace(
            ['<h2>'.self::MARKER.'</h2>'."\n".'<p>'.self::TEXT.'</p>'."\n", "\n".'<p><strong>'.self::MARKER.'</strong> — '.self::TEXT.'</p>'],
            '',
            (string) $page->contenu,
        );

        DB::table('pages')->where('id', $page->id)->update(['contenu' => $content]);
    }

    private function normalize(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\r\n", "\n", $html)));
    }
};
