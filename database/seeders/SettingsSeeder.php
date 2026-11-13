<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Crée les réglages du collectif avec les textes actuels du site.
 * Ne remplace jamais une valeur déjà enregistrée : on peut le relancer sans risque.
 *   php artisan db:seed --class=SettingsSeeder
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $legacy = $this->legacyValues();
        $created = 0;

        foreach (Settings::definitions() as $key => $definition) {
            $value = array_key_exists($key, $legacy) ? $legacy[$key] : ($definition['default'] ?? null);

            $setting = Setting::firstOrCreate(['key' => $key], [
                'group' => $definition['group'],
                'type' => $definition['type'],
                'value' => Settings::serialize($value, $definition['type']),
            ]);

            $created += $setting->wasRecentlyCreated ? 1 : 0;
        }

        Settings::flush();
        $this->command?->info("{$created} réglage(s) créé(s), les autres étaient déjà enregistrés.");
    }

    /**
     * Reprend ce que l'administration enregistrait avant les réglages (table platform_settings).
     */
    private function legacyValues(): array
    {
        if (! Schema::hasTable('platform_settings')) {
            return [];
        }

        $values = [];
        $collective = PlatformSetting::query()->where('key', 'collective_text')->value('value');
        if (is_array($collective) && filled($collective['text'] ?? null)) {
            $values['presentation.collectif_court'] = trim($collective['text']);
        }

        $moderation = PlatformSetting::query()->where('key', 'moderation')->value('value');
        if (is_array($moderation) && array_key_exists('enabled', $moderation)) {
            $values['regles.validation_avant_publication'] = (bool) $moderation['enabled'];
        }

        return $values;
    }
}
