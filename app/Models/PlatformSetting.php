<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function defaultDashboardThemes(): array
    {
        return [
            'visitor' => [
                'label' => 'Visiteur',
                'background' => 'linear-gradient(180deg,#fff8ef 0%,#fffdf8 38%,#f4f8ff 100%)',
                'panel' => '#ffffff',
                'text' => '#2b183d',
                'accent' => '#ef476f',
            ],
            'writer' => [
                'label' => 'Écrivain',
                'background' => 'linear-gradient(180deg,#7a669f 0%,#c084fc 22%,#f6e6ff 52%,#ffd980 78%,#fff7ed 100%)',
                'panel' => '#fffaf4',
                'text' => '#304438',
                'accent' => '#7a669f',
            ],
            'artist' => [
                'label' => 'Illustrateur',
                'background' => '#efefef',
                'panel' => '#ffffff',
                'text' => '#181818',
                'accent' => '#ffb703',
            ],
        ];
    }

    public static function dashboardThemes(): array
    {
        $defaults = self::defaultDashboardThemes();
        $saved = self::query()->where('key', 'dashboard_themes')->value('value') ?? [];

        foreach ($defaults as $type => $theme) {
            $defaults[$type] = array_merge($theme, $saved[$type] ?? []);
        }

        return $defaults;
    }

    public static function updateDashboardThemes(array $themes): void
    {
        self::query()->updateOrCreate(
            ['key' => 'dashboard_themes'],
            ['value' => $themes]
        );
    }
}
