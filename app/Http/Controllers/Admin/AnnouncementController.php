<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Support\AdminLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran « Annonces » (admins) : bandeau fin en haut de toutes les pages publiques.
 */
class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $annonces = Annonce::latest()->latest('id')->get();
        $editing = $request->filled('modifier') ? $annonces->firstWhere('id', (int) $request->query('modifier')) : null;

        return view('admin.announcements.index', [
            'annonces' => $annonces,
            'editing' => $editing,
            'current' => Annonce::active()->latest()->latest('id')->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $annonce = Annonce::create($this->validated($request));
        AdminLogger::log('annonce.modifiee', $annonce, null, null, $this->snapshot($annonce));

        return redirect()->route('admin.announcements.index')->with('status', 'Annonce créée.');
    }

    public function update(Request $request, Annonce $annonce): RedirectResponse
    {
        $before = $this->snapshot($annonce);
        $annonce->update($this->validated($request));
        AdminLogger::log('annonce.modifiee', $annonce, null, $before, $this->snapshot($annonce));

        return redirect()->route('admin.announcements.index')->with('status', 'Annonce mise à jour.');
    }

    /**
     * Activer / désactiver en un clic.
     */
    public function toggle(Annonce $annonce): RedirectResponse
    {
        $before = $this->snapshot($annonce);
        $annonce->update(['actif' => ! $annonce->actif]);
        AdminLogger::log('annonce.modifiee', $annonce, null, $before, $this->snapshot($annonce));

        return redirect()->route('admin.announcements.index')
            ->with('status', $annonce->actif ? 'Annonce activée.' : 'Annonce désactivée.');
    }

    public function destroy(Annonce $annonce): RedirectResponse
    {
        $before = $this->snapshot($annonce);
        $annonce->delete();
        AdminLogger::log('annonce.modifiee', $annonce, null, $before, null);

        return redirect()->route('admin.announcements.index')->with('status', 'Annonce supprimée.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'texte' => ['required', 'string', 'max:'.Annonce::TEXT_MAX],
            'lien' => ['nullable', 'string', 'max:255', 'regex:#^(https?://\S+|/\S*)$#i'],
            'debut' => ['nullable', 'date'],
            'fin' => ['nullable', 'date', 'after_or_equal:debut'],
            'actif' => ['nullable', 'boolean'],
        ], [
            'texte.required' => 'Écris le texte de l’annonce.',
            'texte.max' => 'L’annonce ne doit pas dépasser '.Annonce::TEXT_MAX.' caractères.',
            'lien.regex' => 'Indique une adresse complète (https://…) ou un chemin du site commençant par « / ».',
            'lien.max' => 'Le lien est trop long.',
            'debut.date' => 'Date de début invalide.',
            'fin.date' => 'Date de fin invalide.',
            'fin.after_or_equal' => 'La fin doit être après le début.',
        ]);

        return [
            'texte' => trim($data['texte']),
            'lien' => $data['lien'] ?? null,
            'debut' => $data['debut'] ?? null,
            'fin' => $data['fin'] ?? null,
            'actif' => $request->boolean('actif'),
        ];
    }

    private function snapshot(Annonce $annonce): array
    {
        return [
            'texte' => $annonce->texte,
            'lien' => $annonce->lien,
            'debut' => $annonce->debut?->toDateTimeString(),
            'fin' => $annonce->fin?->toDateTimeString(),
            'actif' => $annonce->actif,
        ];
    }
}
