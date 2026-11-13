<?php

namespace App\Http\Middleware;

use App\Support\AnimationMedia;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Espace « Mes animations » : masqué (404) si la rubrique est désactivée (anim.active),
 * réservé aux comptes illustrateurs.
 */
class EnsureAnimationStudio
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(AnimationMedia::enabled(), 404);
        abort_unless($request->user()?->isArtist(), 403, 'Les animations sont réservées aux comptes illustrateurs.');

        return $next($request);
    }
}
