<?php

namespace App\Jobs;

use App\Models\ComicPage;
use App\Support\ComicImages;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Prépare une planche envoyée (versions WebP, miniature, découpe webtoon) hors de la requête d'envoi.
 */
class ProcessComicPage implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public int $pageId)
    {
    }

    public function handle(): void
    {
        $page = ComicPage::find($this->pageId);

        if ($page && $page->statut_traitement !== ComicPage::READY) {
            ComicImages::process($page);
        }
    }

    public function failed(?Throwable $exception): void
    {
        ComicPage::whereKey($this->pageId)->update([
            'statut_traitement' => ComicPage::FAILED,
            'erreur' => 'La préparation de la planche a échoué. Remplace-la ou renvoie-la.',
        ]);
    }
}
