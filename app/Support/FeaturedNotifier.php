<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\Writer;
use App\Notifications\MemberActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Prévient un membre qu'il est « Artiste du jour » ou « Plume du jour », une seule fois par jour.
 * Appelé dès que la sélection du jour est connue (première visite de la journée ou choix de l'admin).
 */
class FeaturedNotifier
{
    public static function notify(Artist|Writer|null $profile): void
    {
        if (! $profile?->user) {
            return;
        }

        $key = 'featured-notified:'.($profile instanceof Artist ? 'artist' : 'writer').':'.today()->toDateString().':'.$profile->id;

        try {
            // Cache::add n'écrit que si la clé n'existe pas : une seule notification par jour.
            if (Cache::add($key, true, now()->endOfDay())) {
                $profile->user->notify(MemberActivity::featured($profile));
            }
        } catch (Throwable $exception) {
            Log::warning('Featured notification failed', ['profile' => $profile->id, 'error' => $exception->getMessage()]);
        }
    }
}
