<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Animations désactivées (admin › Réglages › Animations, `anim.active`) : 404.
 * Rien n'est supprimé, les pages réapparaissent dès que le réglage est réactivé.
 */
class EnsureAnimationsEnabled
{
    public static function enabled(): bool
    {
        return (bool) setting('anim.active', true);
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(self::enabled(), 404);

        return $next($request);
    }
}
