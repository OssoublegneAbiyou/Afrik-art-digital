<?php

namespace App\Support;

/**
 * « Continuer avec Google » : état de la configuration et chemins de retour.
 *
 * - enabled() : le bouton s'affiche seulement si l'ID client ET le secret sont renseignés (.env)
 *   et si le réglage « auth.google_actif » est coché. Sinon : aucun bouton, aucune erreur.
 * - safePath() : n'accepte qu'un chemin interne (« /recits/12 »), jamais une adresse externe
 *   (« https://… », « //site », « /\site »), pour éviter les redirections ouvertes.
 */
class GoogleAuth
{
    /** Indicateur de session : liaison d'un compte Google depuis « Mon compte ». */
    public const LINK_SESSION_KEY = 'google_link_user_id';

    /** Message de bienvenue (flash) affiché après la création d'un compte via Google. */
    public const WELCOME_SESSION_KEY = 'google_welcome';

    public static function hasClientId(): bool
    {
        return filled(config('services.google.client_id'));
    }

    public static function hasClientSecret(): bool
    {
        return filled(config('services.google.client_secret'));
    }

    public static function configured(): bool
    {
        return self::hasClientId() && self::hasClientSecret();
    }

    public static function enabled(): bool
    {
        return self::configured() && (bool) setting('auth.google_actif', true);
    }

    public static function registrationOpen(): bool
    {
        return (bool) setting('auth.google_inscription', true);
    }

    /**
     * Chemin interne sûr (avec sa requête éventuelle), ou null.
     */
    public static function safePath(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);

        if ($path === '' || strlen($path) > 2000) {
            return null;
        }

        // Un seul « / » au début ; ni « // », ni antislash, ni caractère de contrôle, ni schéma.
        if ($path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '\\')
            || preg_match('/[\x00-\x1F\x7F\s]/', $path) || str_contains(explode('?', $path, 2)[0], '//')) {
            return null;
        }

        if (preg_match('#^/[a-z][a-z0-9+.\-]*:#i', $path) || parse_url($path, PHP_URL_SCHEME) || parse_url($path, PHP_URL_HOST)) {
            return null;
        }

        // Pas de retour vers les pages de connexion elles-mêmes (boucle).
        if (preg_match('#^/(login|logout|auth/google|compte/google|forgot-password|reset-password)(/|\?|$)#i', $path)) {
            return null;
        }

        return $path;
    }

    /**
     * Adresse de la page de connexion qui ramène ensuite sur la page courante.
     */
    public static function loginUrl(?string $path = null): string
    {
        $path = self::safePath($path ?? request()->getRequestUri());

        return $path ? route('login', ['retour' => $path]) : route('login');
    }

    /**
     * Mémorise la page d'origine pour après la connexion (e-mail ou Google).
     */
    public static function rememberReturn(mixed $path): void
    {
        $path = self::safePath($path);

        if ($path !== null) {
            session()->put('url.intended', url($path));
        }
    }
}
