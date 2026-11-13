<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Support\AdminLogger;
use App\Support\Moderation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran « Signalements » : signalements ouverts regroupés par œuvre.
 */
class ReportController extends Controller
{
    public function index(): View
    {
        $groups = Report::with(['reportable', 'user'])
            ->where('status', Report::OPEN)->latest()->get()
            ->filter(fn (Report $report) => $report->reportable !== null)
            ->groupBy(fn (Report $report) => $report->reportable_type.':'.$report->reportable_id)
            ->values();

        $recent = Report::with('reportable')
            ->where('status', '!=', Report::OPEN)->latest('resolved_at')->take(10)->get()
            ->filter(fn (Report $report) => $report->reportable !== null)
            ->unique(fn (Report $report) => $report->reportable_type.':'.$report->reportable_id);

        return view('admin.reports.index', compact('groups', 'recent'));
    }

    /**
     * « Masquer l'œuvre » (action=remove, motif envoyé à l'auteur) ou « Classer sans suite » (action=dismiss).
     * Tous les signalements ouverts sur la même œuvre sont traités ensemble.
     */
    public function resolve(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:dismiss,remove'],
            'note' => ['nullable', 'required_if:action,remove', 'string', 'max:500'],
            // Récit dessiné signalé sur un chapitre : masquer ce chapitre ou toute la série.
            'scope' => ['nullable', 'in:chapitre,serie'],
        ], [
            'note.required_if' => 'Indique le motif du retrait : l’auteur le verra.',
            'note.max' => 'Le motif ne doit pas dépasser 500 caractères.',
        ]);

        $work = $report->reportable;
        $remove = $data['action'] === 'remove';
        $target = $work instanceof \App\Models\ComicChapter && ($data['scope'] ?? null) === 'serie'
            ? \App\Models\Comic::withUnpublished()->find($work->comic_id)
            : $work;

        if ($remove && $target) {
            Moderation::reject($target, $data['note']);
        }

        $count = Report::where('reportable_type', $report->reportable_type)
            ->where('reportable_id', $report->reportable_id)
            ->where('status', Report::OPEN)
            ->update([
                'status' => $remove ? Report::ACTIONED : Report::DISMISSED,
                'resolved_at' => now(),
            ]);

        AdminLogger::log(
            $remove ? 'signalement.oeuvre_retiree' : 'signalement.classe',
            ($remove ? $target : $work) ?? $report,
            null,
            null,
            array_filter(['titre' => ($remove ? $target : $work)?->title, 'signalements' => $count, 'motif' => $remove ? $data['note'] : null]),
        );

        return redirect()->route('admin.reports.index')->with('status', $remove
            ? 'Œuvre masquée : elle n’est plus visible sur le site et son auteur a reçu le motif.'
            : 'Signalement classé sans suite.');
    }
}
