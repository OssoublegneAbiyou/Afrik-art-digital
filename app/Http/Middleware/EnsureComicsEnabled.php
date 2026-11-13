<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rubrique « Récits dessinés » désactivée (admin › Réglages › Récits dessinés, `bd.active`) : 404.
 * Rien n'est supprimé, les pages réapparaissent dès que le réglage est réactivé.
 */
class EnsureComicsEnabled
{
    public static function enabled(): bool
    {
        return (bool) setting('bd.active', true);
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(self::enabled(), 404);

        return $next($request);
    }
}
