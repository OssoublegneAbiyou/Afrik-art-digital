<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\AdminLogger;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran « Pages » (admins) : mentions légales, règles de publication, confidentialité.
 */
class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('id')->get()]);
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:120'],
            'contenu' => ['required', 'string', 'max:50000'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'publie' => ['nullable', 'boolean'],
        ], [
            'titre.required' => 'Le titre est obligatoire.',
            'titre.max' => 'Le titre ne doit pas dépasser 120 caractères.',
            'contenu.required' => 'Le contenu ne peut pas être vide.',
            'contenu.max' => 'Le contenu est trop long (50 000 caractères au plus).',
            'meta_description.max' => 'La description ne doit pas dépasser 160 caractères.',
        ]);

        $contenu = RichText::clean($data['contenu']);

        if (trim(strip_tags($contenu)) === '') {
            return back()->withErrors(['contenu' => 'Le contenu ne peut pas être vide.'])->withInput();
        }

        $before = $page->only(['titre', 'contenu', 'meta_description', 'publie']);

        $page->update([
            'titre' => $data['titre'],
            'contenu' => $contenu,
            'meta_description' => $data['meta_description'] ?? null,
            'publie' => $request->boolean('publie'),
        ]);

        AdminLogger::log('page.modifiee', $page, null, $before, $page->only(['titre', 'contenu', 'meta_description', 'publie']));

        return redirect()->route('admin.pages.edit', $page)->with('status', $page->publie
            ? 'Page « '.$page->titre.' » enregistrée et en ligne.'
            : 'Page « '.$page->titre.' » enregistrée (non publiée : elle n’apparaît pas sur le site).');
    }
}
