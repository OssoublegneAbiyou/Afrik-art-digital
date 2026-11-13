<?php

namespace App\Http\Controllers\ComicStudio;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\ComicPage;
use App\Notifications\MemberActivity;
use App\Support\AdminNotifier;
use App\Support\ComicImages;
use App\Support\ComicPublisher;
use App\Support\ComicStudio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Chapitres : création (séries), étape 2 « Pages », étape 3 « Publication », modification, suppression.
 * Pour un one-shot, l'unique chapitre (numéro 1) est géré sans jamais parler de chapitre.
 */
class ChapterController extends Controller
{
    public function create(Comic $comic): View|RedirectResponse
    {
        Gate::authorize('manage', $comic);

        if ($comic->isOneShot()) {
            return redirect()->route('comics.studio.manage', $comic);
        }

        return view('comics.studio.chapter-form', [
            'comic' => $comic,
            'chapter' => null,
            'nextNumber' => ComicStudio::nextNumber($comic),
        ]);
    }

    public function store(Request $request, Comic $comic): RedirectResponse
    {
        Gate::authorize('manage', $comic);
        abort_if($comic->isOneShot(), 404);

        $data = $this->validated($request, $comic, null);

        $chapter = new ComicChapter([
            'comic_id' => $comic->id,
            'numero' => $data['numero'],
            'titre' => $data['titre'] ?? null,
            'note_auteur' => $data['note_auteur'] ?? null,
        ]);
        $chapter->published_at = null;
        $chapter->rights_certified_at = now();
        $chapter->save();

        return redirect()->route('comics.studio.chapters.pages', [$comic, $chapter])
            ->with('success', 'Chapitre '.$chapter->numberLabel().' créé. Ajoute maintenant ses planches.');
    }

    /** Étape 2 : planches. */
    public function pages(Request $request, Comic $comic, ComicChapter $chapter): View
    {
        $this->authorizeChapter($comic, $chapter);

        return view('comics.studio.pages', [
            'comic' => $comic,
            'chapter' => $chapter,
            'state' => ComicStudio::chapterPayload($comic, $chapter, $request->user()),
        ]);
    }

    public function update(Request $request, Comic $comic, ComicChapter $chapter): RedirectResponse
    {
        $this->authorizeChapter($comic, $chapter);

        $data = $this->validated($request, $comic, $chapter);
        $chapter->fill([
            'titre' => $comic->isOneShot() ? null : ($data['titre'] ?? null),
            'note_auteur' => $data['note_auteur'] ?? null,
        ]);

        if (! $comic->isOneShot() && isset($data['numero'])) {
            $chapter->numero = $data['numero'];
        }

        $chapter->save();

        return $request->input('retour') === 'pages'
            ? redirect()->route('comics.studio.chapters.pages', [$comic, $chapter])->with('success', 'Informations enregistrées.')
            : redirect()->route('comics.studio.manage', $comic)->with('success', 'Chapitre '.$chapter->numberLabel().' enregistré.');
    }

    public function destroy(Comic $comic, ComicChapter $chapter): RedirectResponse
    {
        $this->authorizeChapter($comic, $chapter);

        if ($comic->allChapters()->count() <= 1) {
            return redirect()->route('comics.studio.manage', $comic)
                ->with('error', $comic->isOneShot()
                    ? 'Pour retirer ce one-shot, supprime le récit entier.'
                    : 'C’est le seul chapitre de la série : supprime plutôt la série entière.');
        }

        $label = $chapter->numberLabel();
        ComicStudio::deleteChapter($chapter);
        ComicStudio::refreshLastChapter($comic);

        return redirect()->route('comics.studio.manage', $comic)
            ->with('success', 'Chapitre '.$label.' supprimé, avec ses planches.');
    }

    /** Étape 3 : aperçu et publication. */
    public function publication(Comic $comic, ComicChapter $chapter): View
    {
        $this->authorizeChapter($comic, $chapter);

        $pages = $chapter->pages()->get();

        return view('comics.studio.publication', [
            'comic' => $comic,
            'chapter' => $chapter,
            'pages' => $pages,
            'problems' => $this->problems($comic, $pages),
            'previewUrl' => Route::has('comics.read') ? route('comics.read', [$comic, $chapter->routeNumber()]) : null,
            'needsReview' => ComicChapter::moderationStatusFor(request()->user()) === ComicChapter::MODERATION_PENDING,
        ]);
    }

    public function publish(Request $request, Comic $comic, ComicChapter $chapter): RedirectResponse
    {
        $this->authorizeChapter($comic, $chapter);

        $data = $request->validate([
            'action' => ['required', Rule::in([ComicChapter::STATUS_PUBLISHED, ComicChapter::STATUS_SCHEDULED, ComicChapter::STATUS_DRAFT])],
            'scheduled_at' => ['nullable', 'required_if:action,'.ComicChapter::STATUS_SCHEDULED, 'date_format:Y-m-d\TH:i'],
        ], [
            'action.required' => 'Choisis : publier, programmer ou garder en brouillon.',
            'scheduled_at.required_if' => 'Indique la date et l’heure de sortie.',
            'scheduled_at.date_format' => 'Date de sortie invalide.',
        ]);

        $user = $request->user();
        $back = redirect()->route('comics.studio.chapters.publication', [$comic, $chapter]);

        if ($data['action'] === ComicChapter::STATUS_DRAFT) {
            $wasVisible = $chapter->isVisible();
            $chapter->update(['published_at' => null]);

            if ($wasVisible) {
                ComicStudio::refreshLastChapter($comic);
            }

            return redirect()->route('comics.studio.manage', $comic)
                ->with('success', 'Brouillon enregistré : tes planches sont conservées, tu pourras publier plus tard.');
        }

        if ($problems = $this->problems($comic, $chapter->pages()->get())) {
            return $back->withErrors(['publication' => $problems]);
        }

        $scheduledAt = null;

        if ($data['action'] === ComicChapter::STATUS_SCHEDULED) {
            $scheduledAt = ComicStudio::parseLocal($data['scheduled_at']);

            if (! $scheduledAt || $scheduledAt->lte(now())) {
                return $back->withErrors(['scheduled_at' => 'Choisis une date et une heure à venir.'])->withInput();
            }
        }

        $wasVisible = $chapter->isVisible();
        // Le récit est-il déjà en ligne (fiche validée et au moins un chapitre visible) ?
        $comicLive = $comic->isVisible() && $comic->chapters()->exists();
        $publishedAt = $scheduledAt ?? ($chapter->isPublished() ? $chapter->published_at : now());
        // Un chapitre déjà en ligne republié « maintenant » ne repasse pas par la validation.
        $moderation = $wasVisible && ! $scheduledAt
            ? ComicChapter::MODERATION_APPROVED
            : ComicChapter::moderationStatusFor($user);
        $pending = $moderation === ComicChapter::MODERATION_PENDING;

        $chapter->forceFill([
            'published_at' => $publishedAt,
            'moderation_status' => $moderation,
            'moderation_note' => null,
            'moderated_at' => $pending ? null : $chapter->moderated_at,
            'rights_certified_at' => $chapter->rights_certified_at ?? now(),
            // Envoi immédiat en attente : l'auteur sait déjà qu'il attend l'équipe (pas d'alerte « retard »).
            'retard_signale_at' => $pending && ! $scheduledAt ? now() : null,
        ])->save();

        // La fiche suit son premier chapitre (date de sortie et validation), sauf si l'équipe l'a masquée.
        if (! $comic->isRejected()) {
            if (! $comicLive || $comic->published_at === null || $comic->published_at->gt($publishedAt)) {
                $comic->published_at = $publishedAt;
            }

            if (! $comicLive) {
                $comic->moderation_status = $moderation;
                $comic->moderated_at = $pending ? null : $comic->moderated_at;
            }

            $comic->save();
        }

        if ($pending) {
            AdminNotifier::send(MemberActivity::toReview($user, 1, 'récit dessiné'), $user);
        } elseif (! $scheduledAt) {
            ComicPublisher::release($chapter);
        }

        $what = $comic->isOneShot() ? 'Ton récit' : 'Le chapitre '.$chapter->numberLabel();

        $message = match (true) {
            $pending && $scheduledAt => $what.' est envoyé à l’équipe pour validation. Sortie prévue le '.ComicStudio::localDate($scheduledAt).' ('.ComicStudio::timezoneLabel().'), une fois validé.',
            $pending => $what.' est envoyé à l’équipe : il sera en ligne après validation. Tu seras prévenu.',
            (bool) $scheduledAt => $what.' sortira le '.ComicStudio::localDate($scheduledAt).' ('.ComicStudio::timezoneLabel().').',
            default => $what.' est en ligne !',
        };

        return redirect()->route('comics.studio.manage', $comic)->with('success', $message);
    }

    // ---------- Aides ----------

    private function authorizeChapter(Comic $comic, ComicChapter $chapter): void
    {
        Gate::authorize('manage', $comic);
        abort_unless($chapter->comic_id === $comic->id, 404);
    }

    /** Ce qui empêche la publication, en clair. */
    private function problems(Comic $comic, $pages): array
    {
        $problems = [];

        if ($pages->isEmpty()) {
            $problems[] = 'Ajoute au moins une planche.';
        }

        if ($count = $pages->where('statut_traitement', ComicPage::PENDING)->count()) {
            $problems[] = $count > 1 ? $count.' planches sont encore en préparation : patiente un instant.' : 'Une planche est encore en préparation : patiente un instant.';
        }

        if ($count = $pages->where('statut_traitement', ComicPage::FAILED)->count()) {
            $problems[] = $count > 1 ? $count.' planches sont en erreur : remplace-les ou supprime-les.' : 'Une planche est en erreur : remplace-la ou supprime-la.';
        }

        if (! $comic->couverture) {
            $problems[] = 'Ajoute une couverture dans la fiche du récit.';
        }

        if ($pages->count() > ComicImages::maxPagesPerChapter()) {
            $problems[] = 'Trop de planches : '.ComicImages::maxPagesPerChapter().' au maximum.';
        }

        return $problems;
    }

    private function validated(Request $request, Comic $comic, ?ComicChapter $chapter): array
    {
        $numberRules = $comic->isOneShot()
            ? ['nullable']
            : [
                $chapter ? 'nullable' : 'required', 'numeric', 'min:0', 'max:9999.9', 'regex:/^\d+([.,]\d)?$/',
                Rule::unique('comic_chapters', 'numero')->where('comic_id', $comic->id)->ignore($chapter?->id),
            ];

        $request->merge(['numero' => $request->filled('numero') ? str_replace(',', '.', (string) $request->input('numero')) : null]);

        return $request->validate([
            'numero' => $numberRules,
            'titre' => ['nullable', 'string', 'max:120'],
            'note_auteur' => ['nullable', 'string', 'max:500'],
        ], [
            'numero.required' => 'Indique le numéro du chapitre.',
            'numero.numeric' => 'Le numéro doit être un nombre (ex. 3 ou 2,5).',
            'numero.regex' => 'Le numéro accepte une décimale au plus (ex. 2,5).',
            'numero.unique' => 'Ce numéro de chapitre existe déjà dans la série.',
            'titre.max' => 'Le titre ne doit pas dépasser 120 caractères.',
            'note_auteur.max' => 'Le mot de l’auteur ne doit pas dépasser 500 caractères.',
        ]);
    }
}
