<?php

namespace App\View\Composers;

use App\Support\Settings;
use Illuminate\View\View;

/**
 * Partage $collectif avec toutes les vues : identité, contacts et réseaux, déjà prêts à afficher
 * (les valeurs vides sont null / absentes : la vue n'a qu'à tester avant d'afficher).
 * Calculé une seule fois par requête.
 */
class CollectifComposer
{
    private const MEMO = 'collectif.view-data';

    public function compose(View $view): void
    {
        if (! app()->bound(self::MEMO)) {
            app()->instance(self::MEMO, self::build());
        }

        $view->with('collectif', app(self::MEMO));
    }

    public static function build(): array
    {
        $email = Settings::get('contact.email');
        $whatsapp = preg_replace('/\D+/', '', (string) Settings::get('contact.whatsapp'));

        return [
            'nom' => Settings::get('identite.nom'),
            'slogan' => Settings::get('identite.slogan'),
            'description_courte' => Settings::get('identite.description_courte'),
            'ville' => Settings::get('identite.ville'),
            'annee_creation' => Settings::get('identite.annee_creation'),
            'logo_url' => Settings::imageUrl('identite.logo'),
            'favicon_url' => Settings::imageUrl('identite.favicon'),
            'image_partage_url' => Settings::imageUrl('identite.image_partage'),
            'photo_groupe_url' => Settings::imageUrl('identite.photo_groupe'),
            'contact' => [
                // E-mail public : seulement s'il est renseigné ET que l'affichage est autorisé.
                'email' => Settings::get('contact.afficher_email') ? $email : null,
                'telephone' => Settings::get('contact.telephone'),
                'whatsapp' => Settings::get('contact.whatsapp'),
                'whatsapp_url' => $whatsapp !== '' ? 'https://wa.me/'.$whatsapp : null,
                'adresse' => Settings::get('contact.adresse'),
            ],
            'reseaux' => Settings::socialLinks(),
            'adhesion_ouverte' => (bool) Settings::get('adhesion.ouverte'),
        ];
    }
}
