<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\User;
use App\Support\AdminLogPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;

/**
 * Journal des modifications (admins, lecture seule).
 */
class LogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'action' => (string) $request->query('action', ''),
            'auteur' => (string) $request->query('auteur', ''),
            'du' => $this->date($request->query('du')),
            'au' => $this->date($request->query('au')),
        ];

        $actions = AdminLog::query()->distinct()->orderBy('action')->pluck('action')
            ->mapWithKeys(fn ($action) => [$action => AdminLogPresenter::actionLabel($action)]);

        $authors = User::query()->whereIn('id', AdminLog::query()->whereNotNull('user_id')->distinct()->select('user_id'))
            ->orderBy('name')->get(['id', 'name']);

        $logs = AdminLog::query()
            ->with('user:id,name')
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['auteur'] === 'systeme', fn ($q) => $q->whereNull('user_id'))
            ->when(ctype_digit($filters['auteur']), fn ($q) => $q->where('user_id', (int) $filters['auteur']))
            ->when($filters['du'], fn ($q, $date) => $q->where('created_at', '>=', $date->copy()->startOfDay()))
            ->when($filters['au'], fn ($q, $date) => $q->where('created_at', '<=', $date->copy()->endOfDay()))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'filters' => array_merge($filters, [
                'du' => $filters['du']?->toDateString(),
                'au' => $filters['au']?->toDateString(),
            ]),
            'actions' => $actions,
            'authors' => $authors,
        ]);
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
