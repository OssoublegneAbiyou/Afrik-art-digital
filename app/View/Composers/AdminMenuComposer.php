<?php

namespace App\View\Composers;

use App\Models\Document;
use App\Models\Illustration;
use App\Models\JoinRequest;
use App\Models\Report;
use Illuminate\View\View;

/**
 * Menu de l'administration : entrées selon le rôle, et pastilles « à traiter ».
 */
class AdminMenuComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        $toReview = Illustration::withUnpublished()->where('moderation_status', Illustration::MODERATION_PENDING)->whereNotNull('published_at')->count()
            + Document::withUnpublished()->where('moderation_status', Document::MODERATION_PENDING)->whereNotNull('published_at')->count()
            + \App\Support\Moderation::pendingChapters()->count();

        $badges = [
            'join-requests' => JoinRequest::pendingCount(),
            'moderation' => $toReview,
            'reports' => Report::openCount(),
        ];

        // [route, libellé, autorisation, clé de pastille]
        $items = [
            ['admin.index', 'Tableau de bord', 'moderate', null],
            ['admin.moderation.index', 'À valider', 'moderate', 'moderation'],
            ['admin.reports.index', 'Signalements', 'moderate', 'reports'],
            ['admin.join-requests.index', 'Demandes d’adhésion', 'moderate', 'join-requests'],
            ['admin.featured.index', 'Mises en avant', 'moderate', null],
            ['admin.settings.edit', 'Réglages du collectif', 'manage-settings', null],
            ['admin.members.index', 'Membres', 'manage-users', null],
            ['admin.taxonomy.index', 'Types & genres', 'manage-settings', null],
            ['admin.pages.index', 'Pages', 'manage-settings', null],
            ['admin.announcements.index', 'Annonces', 'manage-settings', null],
            ['admin.logs.index', 'Journal', 'manage-users', null],
        ];

        $menu = collect($items)
            ->filter(fn ($item) => $user?->can($item[2]) && \Illuminate\Support\Facades\Route::has($item[0]))
            ->map(fn ($item) => [
                'route' => $item[0],
                'label' => $item[1],
                'badge' => $item[3] ? ($badges[$item[3]] ?? 0) : 0,
                // Actif aussi sur les sous-pages (admin.pages.edit pour admin.pages.index…).
                'active' => request()->routeIs($item[0] === 'admin.index' ? 'admin.index' : preg_replace('/\.[a-z]+$/', '.*', $item[0])),
            ])
            ->values();

        $view->with('adminMenu', $menu);
    }
}
