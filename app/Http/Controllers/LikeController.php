<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureAnimationsEnabled;
use App\Models\Animation;
use App\Models\Comic;
use App\Models\Document;
use App\Models\Illustration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    private const TYPES = [
        'illustration' => Illustration::class,
        'document' => Document::class,
        'comic' => Comic::class,
        'animation' => Animation::class,
    ];

    /**
     * Ajoute ou retire un « J'aime ». Répond en JSON pour le bouton (fetch), sinon redirige.
     */
    public function toggle(Request $request, string $type, int $id): JsonResponse|RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $model = self::TYPES[$type]::findOrFail($id);
        // Animation pas encore convertie, ou rubrique désactivée : pas publique.
        abort_if($model instanceof Animation && (! $model->isVisible() || ! EnsureAnimationsEnabled::enabled()), 404);
        $liked = $model->toggleLikeFor($request->user());
        $count = $model->likes()->count();

        if ($request->expectsJson()) {
            return response()->json(['liked' => $liked, 'count' => $count]);
        }

        return back();
    }
}
