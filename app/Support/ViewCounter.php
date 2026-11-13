<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Illustration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Compte une vue par visiteur et par session (rafraîchir la page ne gonfle pas le compteur).
 * L'auteur ne compte pas ses propres visites, et un brouillon n'est jamais compté.
 */
class ViewCounter
{
    public static function record(Illustration|Document $work): void
    {
        $user = auth()->user();

        if (! $work->isVisible() || $work->isOwnedBy($user)) {
            return;
        }

        $key = 'viewed.'.$work->getTable().'.'.$work->id;

        if (session()->has($key)) {
            return;
        }

        session()->put($key, true);
        // Sans toucher à updated_at : une vue n'est pas une modification.
        DB::table($work->getTable())->where('id', $work->id)->increment('view_count');
    }

    /**
     * Récits dessinés : une vue par visiteur et par jour (calendaire). Membre : son identifiant ;
     * visiteur : empreinte IP + navigateur (hachée, jamais stockée en clair). Mêmes exclusions que record().
     * Renvoie true si la vue a été comptée.
     */
    public static function recordDaily(Model $work): bool
    {
        $user = auth()->user();

        if (! $work->isVisible() || $work->isOwnedBy($user)) {
            return false;
        }

        $visitor = $user ? 'u'.$user->id : 'g'.sha1(request()->ip().'|'.request()->userAgent());
        $key = 'vues:'.$work->getTable().':'.$work->id.':'.today()->toDateString().':'.$visitor;

        if (! Cache::add($key, true, now()->endOfDay())) {
            return false;
        }

        DB::table($work->getTable())->where('id', $work->id)->increment('view_count');

        return true;
    }
}
