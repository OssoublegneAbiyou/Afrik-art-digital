<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\JoinRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Tableau de bord de l'administration : ce qui attend l'équipe, chiffres du collectif, journal récent.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $toReview = Illustration::withUnpublished()->where('moderation_status', Illustration::MODERATION_PENDING)->whereNotNull('published_at')->count()
            + Document::withUnpublished()->where('moderation_status', Document::MODERATION_PENDING)->whereNotNull('published_at')->count();

        // [libellé, valeur, route, alerte si > 0]
        $todo = [
            ['Nouvelles demandes d’adhésion', JoinRequest::pendingCount(), 'admin.join-requests.index'],
            ['Œuvres à valider', $toReview, 'admin.moderation.index'],
            ['Signalements ouverts', Report::openCount(), 'admin.reports.index'],
        ];

        $collective = [
            ['Membres', User::whereIn('account_type', ['artist', 'writer'])->count(), 'admin.members.index'],
            ['Illustrations publiées', Illustration::count(), null],
            ['Récits publiés', Document::count(), null],
        ];

        // [route, libellé, autorisation]
        $shortcuts = collect([
            ['admin.join-requests.index', 'Traiter les demandes d’adhésion', 'moderate'],
            ['admin.moderation.index', 'Valider les œuvres', 'moderate'],
            ['admin.featured.index', 'Choisir l’artiste et la plume du jour', 'moderate'],
            ['admin.settings.edit', 'Modifier les réglages du collectif', 'manage-settings'],
            ['admin.announcements.index', 'Publier une annonce', 'manage-settings'],
            ['admin.pages.index', 'Modifier les pages', 'manage-settings'],
            ['admin.members.index', 'Gérer les membres et les rôles', 'manage-users'],
            ['admin.logs.index', 'Consulter le journal', 'manage-users'],
        ])->filter(fn ($item) => Route::has($item[0]) && $user->can($item[2]))->values();

        return view('admin.dashboard', [
            'todo' => $todo,
            'collective' => $collective,
            'shortcuts' => $shortcuts,
            'logs' => $user->can('manage-users')
                ? AdminLog::with('user')->latest('id')->take(8)->get()
                : collect(),
        ]);
    }
}
