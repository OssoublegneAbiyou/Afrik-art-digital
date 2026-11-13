<?php

namespace App\Support;

/**
 * Règles de la plateforme (réglages « regles.* », modifiables dans l'admin) sous une forme prête à l'emploi.
 * Les valeurs par défaut sont celles du catalogue config/collectif.php.
 */
class PlatformRules
{
    public const DOWNLOAD_ALLOWED = 'autorise';

    public const DOWNLOAD_AUTHOR_CHOICE = 'choix_auteur';

    public const DOWNLOAD_FORBIDDEN = 'interdit';

    /** Illustrations au maximum par membre. */
    public static function maxWorks(): int
    {
        return max(1, (int) setting('regles.max_oeuvres_par_membre', 20));
    }

    /** Taille maximale d'une image, en Mo. */
    public static function maxImageMb(): int
    {
        return max(1, (int) setting('regles.taille_max_image_mo', 20));
    }

    /** Même limite en Ko, pour la règle de validation « max: ». */
    public static function maxImageKb(): int
    {
        return self::maxImageMb() * 1024;
    }

    /** Images par envoi groupé. */
    public static function imagesPerUpload(): int
    {
        return max(1, (int) setting('regles.images_par_envoi', 10));
    }

    /** Stockage par membre, en Go. */
    public static function storageGb(): int
    {
        return max(1, (int) setting('regles.stockage_max_go', 1));
    }

    /** « 1 Go », « 5 Go »… */
    public static function storageLabel(): string
    {
        return self::storageGb().' Go';
    }

    /** autorise | choix_auteur | interdit */
    public static function downloadMode(): string
    {
        $mode = (string) setting('regles.telechargement_recits', self::DOWNLOAD_AUTHOR_CHOICE);

        return in_array($mode, [self::DOWNLOAD_ALLOWED, self::DOWNLOAD_AUTHOR_CHOICE, self::DOWNLOAD_FORBIDDEN], true)
            ? $mode
            : self::DOWNLOAD_AUTHOR_CHOICE;
    }

    /** L'auteur choisit-il lui-même (case « Autoriser le téléchargement ») ? */
    public static function authorChoosesDownload(): bool
    {
        return self::downloadMode() === self::DOWNLOAD_AUTHOR_CHOICE;
    }
}
