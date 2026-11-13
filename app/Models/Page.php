<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Page éditable par l'équipe (mentions légales, règles de publication, confidentialité…),
 * affichée dans l'univers Pont. Le contenu est du texte riche assaini (App\Support\RichText).
 */
class Page extends Model
{
    public const FOOTER_CACHE_KEY = 'pages.footer';

    // URL propres de certaines pages (sinon /pages/{slug}).
    public const ROUTES = [
        'mentions-legales' => 'public.legal',
        'regles-de-publication' => 'pages.rules',
        'confidentialite' => 'pages.privacy',
    ];

    protected $fillable = ['slug', 'titre', 'contenu', 'meta_description', 'publie'];

    protected function casts(): array
    {
        return ['publie' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::FOOTER_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::FOOTER_CACHE_KEY));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        $route = self::ROUTES[$this->slug] ?? null;

        return $route && Route::has($route) ? route($route) : route('pages.show', $this->slug);
    }

    /**
     * Liens du pied de page : pages publiées, mises en cache (vidé à chaque enregistrement).
     *
     * @return Collection<int, array{slug: string, titre: string, url: string}>
     */
    public static function footerLinks(): Collection
    {
        try {
            $pages = Cache::rememberForever(self::FOOTER_CACHE_KEY, fn () => static::query()
                ->where('publie', true)->orderBy('id')->get(['slug', 'titre'])
                ->map(fn (Page $page) => ['slug' => $page->slug, 'titre' => $page->titre])
                ->all());
        } catch (Throwable) {
            // Avant la migration : rien à afficher.
            return collect();
        }

        return collect($pages)->map(fn (array $page) => [
            'slug' => $page['slug'],
            'titre' => $page['titre'],
            'url' => (new static(['slug' => $page['slug']]))->url(),
        ]);
    }
}
