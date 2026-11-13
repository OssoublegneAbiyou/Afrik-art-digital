<?php

namespace App\Support;

use Illuminate\Support\Str;

class SocialLink
{
    private const BASES = [
        'instagram' => 'https://instagram.com/',
        'twitter' => 'https://x.com/',
        'facebook' => 'https://facebook.com/',
        'youtube' => 'https://youtube.com/@',
        'behance' => 'https://behance.net/',
        'tiktok' => 'https://www.tiktok.com/@',
    ];

    /**
     * Turn a stored handle or partial URL into an absolute external URL.
     */
    public static function url(string $network, ?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        // "instagram.com/handle", "www.site.com"...
        if (Str::contains($value, '.') && ! Str::startsWith($value, '@')) {
            return 'https://'.ltrim($value, '/');
        }

        $handle = ltrim($value, '@/');

        if ($handle === '' || ! isset(self::BASES[$network])) {
            return null;
        }

        return self::BASES[$network].$handle;
    }

    /**
     * @param  array<string, array{0: string, 1: ?string}>  $links  label => [network, value]
     * @return array<int, array{label: string, url: string}>
     */
    public static function collect(array $links): array
    {
        return collect($links)
            ->map(fn ($link, $label) => ['label' => $label, 'url' => self::url($link[0], $link[1])])
            ->filter(fn ($link) => $link['url'] !== null)
            ->values()
            ->all();
    }
}
