<?php

namespace App\Http\Controllers;

use App\Models\Animation;
use App\Support\ViewCounter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Animations, côté public : fiche, favoris et comptage des vues.
 * La liste est le filtre « Animé » de la galerie (/illustrations?format=anime, PublicController::illustrations).
 * La liaison de route ({animation:slug}) n'ouvre une animation non visible (brouillon, en attente, refusée,
 * non convertie) qu'à son auteur et à l'équipe : voir Animation::isVisible() et HasPublication::resolveRouteBinding().
 */
class AnimationController extends Controller
{
    public function show(Request $request, Animation $animation): View
    {
        $user = $request->user();
        $animation->load(['user', 'artist']);
        $isOwner = $animation->isOwnedBy($user);

        // Contenu sensible : avertissement avant la lecture (sauf pour l'auteur), comme le lecteur de récits dessinés.
        $warning = $animation->contenu_sensible && ! $isOwner && $request->query('avertissement') !== 'lu';

        $sameStudio = Animation::query()->listed()
            ->where('user_id', $animation->user_id)
            ->whereKeyNot($animation->id)
            ->with(['user', 'artist'])
            ->withCount('likes')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        return view('animations.show', [
            'animation' => $animation,
            'preview' => ! $animation->isVisible(),
            'isOwner' => $isOwner,
            'warning' => $warning,
            'isFavorite' => $user ? $animation->favorites()->where('user_id', $user->id)->exists() : false,
            'sameStudio' => $sameStudio,
        ]);
    }

    /**
     * Ajouter / retirer des favoris (« Ma collection »).
     */
    public function favorite(Request $request, Animation $animation): JsonResponse|RedirectResponse
    {
        abort_unless($animation->isVisible(), 404);

        $user = $request->user();
        $existing = $animation->favorites()->where('user_id', $user->id)->first();
        $existing ? $existing->delete() : $animation->favorites()->create(['user_id' => $user->id]);
        $favorite = ! $existing;

        if ($request->expectsJson()) {
            return response()->json(['favorite' => $favorite]);
        }

        return back()->with('animation_status', $favorite
            ? '« '.$animation->titre.' » est dans tes favoris.'
            : '« '.$animation->titre.' » a été retiré de tes favoris.');
    }

    /**
     * Vue d'une animation, envoyée par le lecteur après ~3 s de lecture d'une boucle ou au lancement d'un clip.
     * Une vue par visiteur et par jour (ViewCounter::recordDaily) ; l'auteur et les aperçus ne comptent pas.
     */
    public function view(Animation $animation): JsonResponse
    {
        return response()->json(['view' => ViewCounter::recordDaily($animation)]);
    }
}
