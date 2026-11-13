<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

/**
 * Réglages du collectif (table « settings »), définis dans config/collectif.php.
 *
 * - Lecture : Settings::get('contact.email') ou le helper setting('contact.email').
 *   La valeur est convertie selon le type déclaré ; sans valeur en base, la valeur par défaut
 *   du catalogue est utilisée (le site reste identique même si le seeder n'a pas été lancé).
 * - Toute la table est mise en cache ; chaque écriture vide le cache.
 */
class Settings
{
    public const CACHE_KEY = 'collectif.settings';

    // Valeurs brutes mémorisées pour la requête en cours, dans le conteneur (et non en statique :
    // un processus de tests enchaîne plusieurs applications).
    private const MEMO = 'collectif.settings.raw';

    public static function definitions(): array
    {
        return config('collectif.settings', []);
    }

    public static function definition(string $key): array
    {
        return self::definitions()[$key] ?? throw new InvalidArgumentException("Réglage inconnu : {$key}");
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $definition = self::definitions()[$key] ?? null;
        $raw = self::raw();

        if (! array_key_exists($key, $raw)) {
            return $definition ? ($definition['default'] ?? $default) : $default;
        }

        return self::cast($raw[$key], $definition['type'] ?? 'text');
    }

    /**
     * Enregistre une valeur (déjà validée) et vide le cache. Renvoie l'ancienne valeur typée.
     */
    public static function set(string $key, mixed $value): mixed
    {
        $definition = self::definition($key);
        $before = self::get($key);

        Setting::updateOrCreate(
            ['key' => $key],
            ['group' => $definition['group'], 'type' => $definition['type'], 'value' => self::serialize($value, $definition['type'])],
        );

        self::flush();

        return $before;
    }

    public static function flush(): void
    {
        app()->forgetInstance(self::MEMO);
        // Données $collectif déjà calculées pour les vues (App\View\Composers\CollectifComposer).
        app()->forgetInstance('collectif.view-data');
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * URL publique d'un réglage image, ou la valeur de repli.
     */
    public static function imageUrl(string $key, ?string $fallback = null): ?string
    {
        $path = self::get($key);

        return filled($path) && Storage::disk('public')->exists($path) ? asset('storage/'.$path) : $fallback;
    }

    /**
     * Réseaux sociaux renseignés, prêts à afficher : [['key' => 'instagram', 'label' => 'Instagram', 'url' => '…'], …].
     */
    public static function socialLinks(): array
    {
        return collect(self::definitions())
            ->filter(fn ($definition) => $definition['group'] === 'reseaux')
            ->map(fn ($definition, $key) => [
                'key' => substr($key, strlen('reseaux.')),
                'label' => $definition['label'],
                'url' => SocialLink::url($definition['network'] ?? 'website', self::get($key)),
            ])
            ->filter(fn ($link) => filled($link['url']))
            ->values()
            ->all();
    }

    /**
     * Remplace {nom} par le nom du collectif (textes de présentation par défaut).
     */
    public static function text(string $key): ?string
    {
        $value = self::get($key);

        return is_string($value) ? str_replace('{nom}', (string) self::get('identite.nom'), $value) : $value;
    }

    // ---------- Interne ----------

    private static function raw(): array
    {
        if (app()->bound(self::MEMO)) {
            return app(self::MEMO);
        }

        try {
            $raw = Cache::get(self::CACHE_KEY);

            if (! is_array($raw)) {
                // Avant la migration (déploiement en cours), valeurs par défaut — sans les mettre en cache.
                $raw = Schema::hasTable('settings') ? Setting::query()->pluck('value', 'key')->all() : null;

                if ($raw !== null) {
                    Cache::forever(self::CACHE_KEY, $raw);
                }
            }
        } catch (Throwable) {
            $raw = null;
        }

        $raw ??= [];

        app()->instance(self::MEMO, $raw);

        return $raw;
    }

    private static function cast(?string $value, string $type): mixed
    {
        if ($value === null) {
            return $type === 'json' ? [] : null;
        }

        return match ($type) {
            'boolean' => in_array($value, ['1', 'true', 'on'], true),
            'integer' => $value === '' ? null : (int) $value,
            'json' => json_decode($value, true) ?: [],
            default => $value === '' ? null : $value,
        };
    }

    public static function serialize(mixed $value, string $type): ?string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode(array_values((array) $value), JSON_UNESCAPED_UNICODE),
            'integer' => $value === null || $value === '' ? null : (string) (int) $value,
            default => $value === null || $value === '' ? null : (string) $value,
        };
    }
}
