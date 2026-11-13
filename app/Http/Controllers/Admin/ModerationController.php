<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminLogger;
use App\Support\Moderation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran « À valider » : œuvres et récits des membres en attente, quand la validation avant publication
 * est activée (réglage regles.validation_avant_publication, écran Réglages).
 */
class ModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.moderation.index', [
            'queue' => Moderation::queue(),
            'moderationEnabled' => (bool) setting('regles.validation_avant_publication', true),
        ]);
    }

    public function approve(string $type, int $id): RedirectResponse
    {
        $work = Moderation::find($type, $id);

        Moderation::approve($work);
        AdminLogger::log('oeuvre.validee', $work, null, null, ['titre' => $work->title, 'type' => $type]);

        if ($work instanceof \App\Models\ComicChapter) {
            return redirect()->route('admin.moderation.index')
                ->with('status', '« '.$work->title.' » est validé'.($work->isPublished() ? ' et en ligne.' : ' (en ligne à la date prévue).').' L’auteur a été prévenu.');
        }

        return redirect()->route('admin.moderation.index')
            ->with('status', '« '.$work->title.' » est publiée'.($work->isPublished() ? ' et en ligne.' : ' (en ligne à la date prévue).').' L’auteur a été prévenu.');
    }

    public function reject(Request $request, string $type, int $id): RedirectResponse
    {
        $work = Moderation::find($type, $id);
        $data = $request->validate(['note' => ['required', 'string', 'max:500']], [
            'note.required' => 'Indique le motif : l’auteur le verra et pourra corriger son œuvre.',
            'note.max' => 'Le motif ne doit pas dépasser 500 caractères.',
        ]);

        Moderation::reject($work, $data['note']);
        AdminLogger::log('oeuvre.refusee', $work, null, null, ['titre' => $work->title, 'type' => $type, 'motif' => $data['note']]);

        return redirect()->route('admin.moderation.index')
            ->with('status', '« '.$work->title.' » est refusée ; l’auteur a reçu le motif.');
    }
}
