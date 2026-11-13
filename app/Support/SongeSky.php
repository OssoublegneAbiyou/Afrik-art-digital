<?php

namespace App\Support;

use App\Models\Genre;

/**
 * Ciels de genre de l'univers « Songe » sous forme de variables CSS. Les genres viennent de la table
 * genres (gérée dans l'admin) ; « all » est le ciel commun (aucun genre, ou genre inconnu).
 * Les feuilles de style choisissent la version jour ou nuit selon body[data-mode].
 */
class SongeSky
{
    public const ALL = [
        'label' => 'Tous',
        'sky_day' => 'linear-gradient(160deg,#FFE9DC,#E9DDFF 45%,#B9C6EE)',
        'ink_day' => '#2A2350',
        'sky_night' => 'linear-gradient(160deg,#1A1440,#120E30 55%,#0B0820)',
        'ink_night' => '#F1EBFF',
        'glow' => '#E9DDFF',
        'tagline' => 'Cinq ciels, cinq façons de rêver. Choisis le tien.',
        'description' => '',
    ];

    private const NUMBERS = [1 => 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze'];

    /**
     * Données d'un ciel ; un genre inconnu ou null retombe sur « all ». Un genre désactivé garde son ciel.
     */
    public static function get(?string $genre): array
    {
        $model = $genre === 'all' ? null : Genre::findBySlug($genre);
        $sky = $model ? self::fromGenre($model) : self::allSky();

        return $sky + [
            // Couleur posée sur l'encre (pilule active) : la teinte la plus proche du ciel.
            'on_ink_day' => self::firstColor($sky['sky_day']),
            'on_ink_night' => self::firstColor($sky['sky_night']),
        ];
    }

    /**
     * Variables CSS à poser en style inline : --sky-d/--sky-n, --ink-d/--ink-n, --glow, --on-ink-d/--on-ink-n.
     */
    public static function style(?string $genre): string
    {
        $sky = self::get($genre);

        return sprintf(
            '--sky-d:%s;--sky-n:%s;--ink-d:%s;--ink-n:%s;--glow:%s;--on-ink-d:%s;--on-ink-n:%s;',
            $sky['sky_day'], $sky['sky_night'], $sky['ink_day'], $sky['ink_night'],
            $sky['glow'], $sky['on_ink_day'], $sky['on_ink_night'],
        );
    }

    /**
     * Tous les ciels proposés (clé => données) : « all » en premier, puis les genres actifs.
     */
    public static function all(): array
    {
        return ['all' => self::get('all')]
            + Genre::actifs()->keys()->mapWithKeys(fn ($key) => [$key => self::get($key)])->all();
    }

    /**
     * « Cinq ciels », « Six ciels »… selon le nombre de genres actifs.
     */
    public static function countLabel(int $count, bool $capitalize = true): string
    {
        $word = self::NUMBERS[$count] ?? (string) $count;
        $label = $word.' '.($count >= 2 ? 'ciels' : 'ciel');

        return $capitalize ? mb_strtoupper(mb_substr($label, 0, 1)).mb_substr($label, 1) : $label;
    }

    /**
     * Ciel d'une couleur saisie dans l'admin : #RRGGBB → dégradé (plus clair → couleur → plus sombre) ;
     * un dégradé déjà composé (valeurs initiales) est gardé tel quel.
     */
    public static function gradient(string $value): string
    {
        $value = trim($value);

        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            return self::isSafeGradient($value) ? $value : self::ALL['sky_day'];
        }

        return sprintf('linear-gradient(160deg,%s,%s 55%%,%s)', self::mix($value, '#FFFFFF', .45), strtoupper($value), self::mix($value, '#000000', .35));
    }

    /**
     * Dégradé linéaire autorisé : uniquement un angle, des couleurs #RRGGBB et des positions en %.
     */
    public static function isSafeGradient(string $value): bool
    {
        return (bool) preg_match('/^linear-gradient\(\s*\d{1,3}deg(\s*,\s*#[0-9A-Fa-f]{6}(\s+\d{1,3}%)?){2,5}\s*\)$/', trim($value));
    }

    public static function firstColor(string $gradient): string
    {
        return preg_match('/#[0-9A-Fa-f]{6}/', $gradient, $match) ? $match[0] : '#2A2350';
    }

    private static function allSky(): array
    {
        $sky = self::ALL;
        $count = Genre::actifs()->count();

        // La phrase commune suit le nombre de ciels (« Cinq ciels, cinq façons de rêver. »).
        if ($count !== 5 && $count >= 2) {
            $sky['tagline'] = self::countLabel($count).', '.(self::NUMBERS[$count] ?? $count).' façons de rêver. Choisis le tien.';
        }

        return $sky;
    }

    private static function fromGenre(Genre $genre): array
    {
        return [
            'label' => $genre->nom,
            'sky_day' => self::gradient($genre->ciel_jour),
            'ink_day' => $genre->encre_jour,
            'sky_night' => self::gradient($genre->ciel_nuit),
            'ink_night' => $genre->encre_nuit,
            'glow' => $genre->lueur_nuit,
            'tagline' => (string) $genre->phrase_accroche,
            'description' => (string) $genre->description,
        ];
    }

    private static function mix(string $hex, string $with, float $ratio): string
    {
        $a = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        $b = sscanf(ltrim($with, '#'), '%02x%02x%02x');

        return sprintf('#%02X%02X%02X', ...array_map(fn ($i) => (int) round($a[$i] + ($b[$i] - $a[$i]) * $ratio), [0, 1, 2]));
    }
}
