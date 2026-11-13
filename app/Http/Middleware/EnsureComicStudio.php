<?php

namespace App\Http\Middleware;

use App\Support\ComicImages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Espace « Mes récits dessinés » : masqué (404) si la rubrique est désactivée (bd.active),
 * réservé aux comptes illustrateurs.
 */
class EnsureComicStudio
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(ComicImages::enabled(), 404);
        abort_unless($request->user()?->isArtist(), 403, 'Les récits dessinés sont réservés aux comptes illustrateurs.');

        return $next($request);
    }
}
