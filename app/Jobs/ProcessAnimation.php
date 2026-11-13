<?php

namespace App\Jobs;

use App\Models\Animation;
use App\Support\AnimationMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Convertit une animation envoyée (vérifications ffprobe, MP4 H.264, WebM facultatif, poster WebP)
 * hors de la requête d'envoi. Un seul essai : une vidéo illisible le resterait au deuxième.
 */
class ProcessAnimation implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $animationId)
    {
        // Le délai du worker suit celui des conversions (config media.timeout) avec une marge.
        $this->timeout = max(120, (int) config('media.timeout', 600) + 60);
    }

    public function handle(): void
    {
        $animation = Animation::withUnpublished()->withTrashed()->find($this->animationId);

        // Seulement si elle attend encore : le secours de la page d'état a pu la convertir entre-temps.
        $claimed = $animation && ! $animation->trashed()
            && Animation::withUnpublished()->whereKey($animation->id)
                ->where('statut_traitement', Animation::QUEUED)
                ->update(['statut_traitement' => Animation::PROCESSING]) === 1;

        if ($claimed) {
            AnimationMedia::process($animation->refresh());
        }
    }

    public function failed(?Throwable $exception): void
    {
        Animation::withUnpublished()->withTrashed()->whereKey($this->animationId)
            ->where('statut_traitement', '!=', Animation::READY)
            ->update([
                'statut_traitement' => Animation::FAILED,
                'erreur' => 'La conversion a pris trop de temps ou a échoué. Relance-la, ou envoie une vidéo plus courte.',
            ]);
    }
}
