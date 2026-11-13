<?php

/*
|--------------------------------------------------------------------------
| Réglages du collectif modifiables depuis l'administration
|--------------------------------------------------------------------------
|
| Source unique de vérité : le seeder (valeurs par défaut), l'écran « Réglages du collectif »
| (libellés, aides, validation) et le service App\Support\Settings (types, valeurs de repli)
| lisent tous ce fichier.
|
| Les valeurs par défaut reprennent MOT POUR MOT les textes affichés avant que les réglages
| existent : juste après le déploiement, le site est identique.
|
| Types : text, textarea, richtext, email, url, phone, image, boolean, integer, json, select.
| « max » : longueur maximale (caractères) ; « items_max » : longueur max de chaque élément d'un json ;
| « items_count » : nombre exact d'éléments ; « items_count_max » : nombre maximal d'éléments ;
| « min » / « max_value » : bornes d'un entier ; « required » : champ obligatoire ;
| « link » : route publique où le réglage est visible (lien « Voir sur le site ↗ » dans l'admin).
|
*/

return [

    'groups' => [
        'identite' => 'Identité',
        'presentation' => 'Présentation',
        'contact' => 'Contact',
        'reseaux' => 'Réseaux sociaux',
        'adhesion' => 'Adhésion',
        'accueil' => 'Mises en avant',
        'decor' => 'Décor',
        'regles' => 'Règles',
        'bd' => 'Récits dessinés',
        'anim' => 'Animations',
        'seo' => 'Référencement',
    ],

    'settings' => [

        // ---------- Identité ----------
        'identite.nom' => [
            'group' => 'identite', 'type' => 'text', 'label' => 'Nom du collectif', 'max' => 60, 'required' => true,
            'help' => 'En-têtes, titre des pages, pied de page et e-mails.',
            'default' => "Afrik'art Digital",
        ],
        'identite.slogan' => [
            'group' => 'identite', 'type' => 'text', 'label' => 'Slogan', 'max' => 60,
            'help' => 'Sous le logo, dans l’univers du Pont (accueil, À propos…).',
            'default' => 'là où le néon rêve',
        ],
        'identite.description_courte' => [
            'group' => 'identite', 'type' => 'text', 'label' => 'Description courte', 'max' => 120,
            'help' => 'Complète le nom dans le titre de l’accueil et dans le pied de page.',
            'default' => "Collectif d'illustrateurs et d'écrivains d'Abidjan",
        ],
        'identite.ville' => [
            'group' => 'identite', 'type' => 'text', 'label' => 'Ville', 'max' => 60,
            'help' => 'Pied de page et accueil.',
            'default' => 'Abidjan',
        ],
        'identite.annee_creation' => [
            'group' => 'identite', 'type' => 'integer', 'label' => 'Année de création', 'min' => 1950, 'max_value' => 2100,
            'help' => 'Affichée sur la page À propos seulement si elle est remplie.',
            'default' => null,
        ],
        'identite.logo' => [
            'group' => 'identite', 'type' => 'image', 'label' => 'Logo',
            'help' => 'Carré de préférence. Sans logo, le monogramme « AD » actuel est affiché.',
            'default' => null,
        ],
        'identite.favicon' => [
            'group' => 'identite', 'type' => 'image', 'label' => 'Icône d’onglet (favicon)',
            'help' => 'Image carrée, 64 × 64 px ou plus. Sans image, l’icône « AD » actuelle est utilisée.',
            'default' => null,
        ],
        'identite.image_partage' => [
            'group' => 'identite', 'type' => 'image', 'label' => 'Image de partage par défaut',
            'help' => 'Aperçu sur WhatsApp, Facebook… quand une page n’a pas d’image propre. Idéalement 1200 × 630 px.',
            'default' => null,
        ],
        'identite.photo_groupe' => [
            'group' => 'identite', 'type' => 'image', 'label' => 'Photo du groupe',
            'help' => 'Affichée sur la page À propos (masquée si vide).',
            'default' => null,
        ],

        // ---------- Présentation ----------
        'presentation.accueil_titre_1' => [
            'group' => 'presentation', 'type' => 'text', 'label' => 'Titre de l’accueil — partie néon', 'max' => 40,
            'help' => 'Affichée en grandes capitales.', 'link' => 'public.index',
            'default' => 'La ville dessine,',
        ],
        'presentation.accueil_titre_2' => [
            'group' => 'presentation', 'type' => 'text', 'label' => 'Titre de l’accueil — partie italique', 'max' => 40,
            'link' => 'public.index',
            'default' => 'la nuit raconte.',
        ],
        'presentation.accueil_intro' => [
            'group' => 'presentation', 'type' => 'textarea', 'label' => 'Paragraphe d’introduction de l’accueil', 'max' => 400,
            'link' => 'public.index',
            'default' => "Illustrations, portfolios immersifs et récits d'un collectif d'artistes et d'écrivains. Une rue de néons pour les traits, un ciel de rêve pour les mots — et un pont entre les deux.",
        ],
        'presentation.collectif_court' => [
            'group' => 'presentation', 'type' => 'textarea', 'label' => 'Présentation courte du collectif', 'max' => 800,
            'help' => 'Section « Le collectif » de l’accueil.', 'link' => 'public.index',
            // Le nom du collectif est inséré à l'affichage à la place de {nom}.
            'default' => "{nom} réunit des illustrateurs et des écrivains d'Abidjan. Chaque membre y publie ses œuvres — illustrations, portfolios, récits et poèmes — pour les faire découvrir librement.",
        ],
        'presentation.a_propos' => [
            'group' => 'presentation', 'type' => 'richtext', 'label' => 'Texte de la page À propos', 'max' => 10000,
            'help' => 'Titres, gras, italique, listes et liens.', 'link' => 'public.about',
            'default' => "<p>{nom} est la vitrine d'un collectif d'illustrateurs et d'écrivains. Chaque membre y publie ses illustrations, ses portfolios immersifs et ses textes, pour faire découvrir une création visuelle et littéraire ancrée à Abidjan et ouverte sur le monde.</p>",
        ],
        'presentation.meta_description' => [
            'group' => 'presentation', 'type' => 'textarea', 'label' => 'Description pour les moteurs de recherche', 'max' => 160,
            'help' => 'Texte sous le titre dans Google et dans les aperçus de partage (160 caractères au plus).',
            'default' => "Afrik'art Digital est la vitrine d'un collectif d'illustrateurs et d'écrivains : illustrations, portfolios immersifs et œuvres littéraires à découvrir.",
        ],

        // ---------- Contact ----------
        'contact.email' => [
            'group' => 'contact', 'type' => 'email', 'label' => 'E-mail du collectif',
            'help' => 'Affiché sur À propos et dans le pied de page ; reçoit aussi les alertes des formulaires.', 'link' => 'public.about',
            'default' => env('SITE_CONTACT_EMAIL'),
        ],
        'contact.afficher_email' => [
            'group' => 'contact', 'type' => 'boolean', 'label' => 'Afficher l’e-mail publiquement',
            'help' => 'Décoché : l’e-mail reste masqué sur le site mais continue de recevoir les alertes.',
            'default' => true,
        ],
        'contact.telephone' => [
            'group' => 'contact', 'type' => 'phone', 'label' => 'Téléphone', 'link' => 'public.about',
            'help' => 'Affiché sur la page À propos. Vide : masqué.',
            'default' => null,
        ],
        'contact.whatsapp' => [
            'group' => 'contact', 'type' => 'phone', 'label' => 'WhatsApp',
            'help' => 'Numéro au format international, par exemple +225 07 00 00 00 00.',
            'default' => env('SITE_WHATSAPP'),
        ],
        'contact.adresse' => [
            'group' => 'contact', 'type' => 'textarea', 'label' => 'Adresse', 'max' => 300, 'link' => 'public.about',
            'help' => 'Affichée sur la page À propos. Vide : masquée.',
            'default' => null,
        ],

        // ---------- Réseaux sociaux ----------
        'reseaux.instagram' => ['group' => 'reseaux', 'type' => 'url', 'label' => 'Instagram', 'network' => 'instagram', 'default' => env('SITE_INSTAGRAM')],
        'reseaux.facebook' => ['group' => 'reseaux', 'type' => 'url', 'label' => 'Facebook', 'network' => 'facebook', 'default' => env('SITE_FACEBOOK')],
        'reseaux.tiktok' => ['group' => 'reseaux', 'type' => 'url', 'label' => 'TikTok', 'network' => 'tiktok', 'default' => null],
        'reseaux.youtube' => ['group' => 'reseaux', 'type' => 'url', 'label' => 'YouTube', 'network' => 'youtube', 'default' => null],
        'reseaux.behance' => ['group' => 'reseaux', 'type' => 'url', 'label' => 'Behance', 'network' => 'behance', 'default' => null],
        'reseaux.x' => ['group' => 'reseaux', 'type' => 'url', 'label' => 'X (Twitter)', 'network' => 'twitter', 'default' => null],
        'reseaux.site' => ['group' => 'reseaux', 'type' => 'url', 'label' => 'Site web', 'network' => 'website', 'default' => null],

        // ---------- Adhésion ----------
        'adhesion.ouverte' => [
            'group' => 'adhesion', 'type' => 'boolean', 'label' => 'Formulaire « Rejoindre » ouvert', 'link' => 'public.about',
            'default' => true,
        ],
        'adhesion.intro' => [
            'group' => 'adhesion', 'type' => 'textarea', 'label' => 'Texte au-dessus du formulaire', 'max' => 600, 'link' => 'public.about',
            'default' => "Les comptes sont créés par l'équipe du collectif. Tu es illustrateur ou écrivain et tu souhaites publier tes œuvres ? Remplis ce formulaire : la demande arrive directement chez l'équipe, qui te répondra par e-mail avec tes accès.",
        ],
        'adhesion.message_ferme' => [
            'group' => 'adhesion', 'type' => 'textarea', 'label' => 'Message quand les adhésions sont fermées', 'max' => 600, 'link' => 'public.about',
            'default' => "Les adhésions sont fermées pour le moment. Suis le collectif sur ses réseaux pour savoir quand elles rouvriront.",
        ],
        'adhesion.message_confirmation' => [
            'group' => 'adhesion', 'type' => 'textarea', 'label' => 'Message après l’envoi d’une demande', 'max' => 600,
            'default' => "Merci, ta demande est bien arrivée. L'équipe regarde ton travail et te répond par e-mail. Pense à vérifier tes courriers indésirables.",
        ],
        'adhesion.email_notification' => [
            'group' => 'adhesion', 'type' => 'email', 'label' => 'E-mail qui reçoit les demandes',
            'help' => 'Vide : l’e-mail du collectif est utilisé.',
            'default' => null,
        ],

        // ---------- Mises en avant ----------
        'accueil.artiste_du_jour_mode' => [
            'group' => 'accueil', 'type' => 'select', 'label' => 'Artiste du jour',
            'options' => ['auto' => 'Tirage automatique quotidien', 'manuel' => 'Choix manuel'],
            'default' => 'auto',
        ],
        'accueil.artiste_du_jour_id' => [
            'group' => 'accueil', 'type' => 'integer', 'label' => 'Artiste choisi (mode manuel)',
            'default' => null,
        ],
        'accueil.plume_du_jour_mode' => [
            'group' => 'accueil', 'type' => 'select', 'label' => 'Plume du jour',
            'options' => ['auto' => 'Tirage automatique quotidien', 'manuel' => 'Choix manuel'],
            'default' => 'auto',
        ],
        'accueil.plume_du_jour_id' => [
            'group' => 'accueil', 'type' => 'integer', 'label' => 'Plume choisie (mode manuel)',
            'default' => null,
        ],
        'accueil.oeuvres_epinglees' => [
            'group' => 'accueil', 'type' => 'json', 'label' => 'Œuvres épinglées',
            'help' => 'Identifiants d’illustrations, dans l’ordre d’affichage.',
            'default' => [],
        ],

        // ---------- Décor (textes des enseignes, purement décoratifs) ----------
        'decor.bandeau_nuit' => [
            'group' => 'decor', 'type' => 'json', 'label' => 'Bandeau défilant de la galerie — nuit', 'items_max' => 40, 'items_count_max' => 12, 'link' => 'public.illustrations',
            'help' => 'Une phrase par ligne (12 au plus), 40 caractères au plus par phrase.',
            'default' => ['LIBÈRE TA CRÉATIVITÉ', '新作', 'FAIS VIBRER TA VILLE', 'TRAIT DE ZIGUEHI', 'COULEURS DE BABI', 'ナイト'],
        ],
        'decor.bandeau_jour' => [
            'group' => 'decor', 'type' => 'json', 'label' => 'Écran LED de la galerie — jour', 'items_max' => 40, 'items_count_max' => 12, 'link' => 'public.illustrations',
            'help' => 'Une phrase par ligne (12 au plus), 40 caractères au plus par phrase.',
            'default' => ['NOUVEAUX DROPS', 'TRAIT DE ZIGUEHI', 'COULEURS DE BABI', 'PLATEAU · COCODY · YOP'],
        ],
        'decor.led_illustrateurs' => [
            'group' => 'decor', 'type' => 'text', 'label' => 'Écran LED de la page Illustrateurs (jour)', 'max' => 40, 'link' => 'public.artists',
            'default' => 'BABI ILLUSTRATION CITY ●',
        ],
        'decor.ecran_drop' => [
            'group' => 'decor', 'type' => 'text', 'label' => 'Écran « nouveau drop » (page Illustrateurs, jour)', 'max' => 40, 'link' => 'public.artists',
            'default' => 'NOUVEAU DROP · 12:00',
        ],
        'decor.panneau_directions' => [
            'group' => 'decor', 'type' => 'json', 'label' => 'Panneau directionnel (3 lignes)', 'items_max' => 40, 'items_count' => 3, 'link' => 'public.artists',
            'help' => 'Exactement 3 lignes : ↑, ← puis →.',
            'default' => ['Plateau · Galerie', 'Cocody · Atelier', 'Yopougon · Street Art'],
        ],

        // ---------- Règles de la plateforme ----------
        'regles.max_oeuvres_par_membre' => [
            'group' => 'regles', 'type' => 'integer', 'label' => 'Illustrations au maximum par membre', 'min' => 1, 'max_value' => 500,
            'default' => 20,
        ],
        'regles.taille_max_image_mo' => [
            'group' => 'regles', 'type' => 'integer', 'label' => 'Taille maximale d’une image (Mo)', 'min' => 1, 'max_value' => 100,
            'default' => 20,
        ],
        'regles.stockage_max_go' => [
            'group' => 'regles', 'type' => 'integer', 'label' => 'Stockage par membre (Go)', 'min' => 1, 'max_value' => 50,
            'default' => 1,
        ],
        'regles.images_par_envoi' => [
            'group' => 'regles', 'type' => 'integer', 'label' => 'Images par envoi groupé', 'min' => 1, 'max_value' => 50,
            'default' => 10,
        ],
        'regles.validation_avant_publication' => [
            'group' => 'regles', 'type' => 'boolean', 'label' => 'Validation avant publication',
            'help' => 'Les nouvelles œuvres des membres attendent l’accord d’un modérateur avant d’apparaître sur le site.',
            // Comportement actuel du site : validation activée.
            'default' => true,
        ],
        'regles.telechargement_recits' => [
            'group' => 'regles', 'type' => 'select', 'label' => 'Téléchargement des récits',
            'options' => ['autorise' => 'Toujours autorisé', 'choix_auteur' => 'Au choix de l’auteur', 'interdit' => 'Toujours interdit'],
            // Comportement actuel du site : chaque auteur choisit (case « Autoriser le téléchargement »).
            'default' => 'choix_auteur',
        ],

        // ---------- Animations courtes (boucles et clips) ----------
        'anim.active' => [
            'group' => 'anim', 'type' => 'boolean', 'label' => 'Activer les animations',
            'help' => 'Décoché : les animations disparaissent du site et de l’espace des illustrateurs (rien n’est supprimé).',
            'default' => true,
        ],
        'anim.duree_max_s' => [
            'group' => 'anim', 'type' => 'integer', 'label' => 'Durée maximale d’une animation (secondes)', 'min' => 1, 'max_value' => 600,
            'default' => 120,
        ],
        'anim.taille_max_mo' => [
            'group' => 'anim', 'type' => 'integer', 'label' => 'Taille maximale d’un fichier envoyé (Mo)', 'min' => 1, 'max_value' => 500,
            'help' => 'Vérifiez aussi upload_max_filesize et post_max_size de PHP sur le serveur.',
            'default' => 100,
        ],
        'anim.formats_acceptes' => [
            'group' => 'anim', 'type' => 'text', 'label' => 'Formats acceptés', 'max' => 60,
            'help' => 'Extensions séparées par des virgules, parmi : mp4, mov, webm, gif.',
            'default' => 'mp4, mov, webm, gif',
        ],
        'anim.stockage_max_go' => [
            'group' => 'anim', 'type' => 'integer', 'label' => 'Stockage des animations par membre (Go)', 'min' => 1, 'max_value' => 50,
            'help' => 'Quota séparé de ceux des illustrations et des récits dessinés.',
            'default' => 2,
        ],

        // ---------- Récits dessinés (one-shots, séries manga / BD / webtoon) ----------
        'bd.active' => [
            'group' => 'bd', 'type' => 'boolean', 'label' => 'Activer les récits dessinés',
            'help' => 'Décoché : la rubrique disparaît du site et de l’espace des illustrateurs (rien n’est supprimé).',
            'default' => true,
        ],
        'bd.pages_max_par_chapitre' => [
            'group' => 'bd', 'type' => 'integer', 'label' => 'Pages au maximum par chapitre', 'min' => 1, 'max_value' => 300,
            'default' => 80,
        ],
        'bd.taille_max_page_mo' => [
            'group' => 'bd', 'type' => 'integer', 'label' => 'Taille maximale d’une planche envoyée (Mo)', 'min' => 1, 'max_value' => 50,
            'default' => 15,
        ],
        'bd.formats_acceptes' => [
            'group' => 'bd', 'type' => 'text', 'label' => 'Formats acceptés', 'max' => 60,
            'help' => 'Extensions séparées par des virgules, parmi : jpg, png, webp.',
            'default' => 'jpg, png, webp',
        ],
        'bd.stockage_max_go' => [
            'group' => 'bd', 'type' => 'integer', 'label' => 'Stockage des récits dessinés par membre (Go)', 'min' => 1, 'max_value' => 50,
            'help' => 'Quota séparé de celui des illustrations.',
            'default' => 3,
        ],
        'bd.largeur_lecture_px' => [
            'group' => 'bd', 'type' => 'integer', 'label' => 'Largeur maximale des planches converties (px)', 'min' => 800, 'max_value' => 2400,
            'default' => 1600,
        ],
        'bd.validation_avant_publication' => [
            'group' => 'bd', 'type' => 'select', 'label' => 'Validation des chapitres avant publication',
            'options' => ['suit' => 'Comme les autres œuvres (règle générale)', 'oui' => 'Toujours', 'non' => 'Jamais'],
            'default' => 'suit',
        ],
        'bd.filigrane' => [
            'group' => 'bd', 'type' => 'boolean', 'label' => 'Filigrane discret sur les planches',
            'help' => 'Ajoute « @auteur · nom du collectif » en bas des planches lors de la conversion (pages envoyées ensuite).',
            'default' => false,
        ],
        'bd.fuseau_horaire' => [
            'group' => 'bd', 'type' => 'select', 'label' => 'Fuseau horaire des publications programmées',
            'options' => ['Africa/Abidjan' => 'Abidjan (GMT)', 'Europe/Paris' => 'Paris'],
            'default' => 'Africa/Abidjan',
        ],

        // ---------- Référencement ----------
        'seo.titre_par_defaut' => [
            'group' => 'seo', 'type' => 'text', 'label' => 'Titre de l’accueil dans Google', 'max' => 70,
            'help' => 'Vide : « Nom — Description courte ».',
            'default' => null,
        ],
        'seo.suffixe_titre' => [
            'group' => 'seo', 'type' => 'text', 'label' => 'Suffixe des titres de pages', 'max' => 40,
            'help' => 'Ajouté après le titre de chaque page. Vide : « | Nom du collectif ».',
            'default' => null,
        ],
        'seo.indexation' => [
            'group' => 'seo', 'type' => 'boolean', 'label' => 'Autoriser les moteurs de recherche',
            'help' => 'Décoché : le site demande à Google de ne pas l’indexer (pratique tant qu’il est en préparation).',
            'default' => true,
        ],
    ],
];
