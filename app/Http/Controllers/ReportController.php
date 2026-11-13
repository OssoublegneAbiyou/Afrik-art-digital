<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureAnimationsEnabled;
use App\Models\Animation;
use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\Report;
use App\Notifications\MemberActivity;
use App\Support\AdminNotifier;
use App\Support\Moderation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Bouton « Signaler » sur les œuvres : ouvert aux visiteurs comme aux membres.
 */
class ReportController extends Controller
{
    /**
     * Récits dessinés : signalables au niveau de la série ou d'un chapitre. Si Moderation::TYPES les contient
     * déjà (géré par la modération), c'est sa définition qui s'applique.
     */
    private const COMIC_TYPES = [
        'comic' => Comic::class,
        'comic_chapter' => ComicChapter::class,
        // Animations : même principe (Moderation::TYPES prend le relais quand il les contient).
        'animation' => Animation::class,
    ];

    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        $types = Moderation::TYPES + self::COMIC_TYPES;
        abort_unless(isset($types[$type]), 404);

        // Seules les œuvres visibles peuvent être signalées (scope « published »).
        $work = $types[$type]::findOrFail($id);

        // Un chapitre n'est signalable que si son récit est lui aussi visible.
        abort_if($work instanceof ComicChapter && ! $work->comic?->isVisible(), 404);

        // Une animation n'est signalable que convertie (prête) et si la rubrique est active.
        abort_if($work instanceof Animation && (! $work->isVisible() || ! EnsureAnimationsEnabled::enabled()), 404);

        // Champ piège anti-robots.
        if (filled($request->input('site_web'))) {
            return back()->with('report_sent', true);
        }

        $data = $request->validateWithBag('report', [
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'message' => ['nullable', 'required_if:reason,autre', 'string', 'max:1000'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'reason.required' => 'Choisis la raison du signalement.',
            'message.required_if' => 'Explique en quelques mots ce qui pose problème.',
            'email.email' => 'Cette adresse e-mail ne semble pas valide.',
        ]);

        $user = $request->user();

        // Un même membre ne signale qu'une fois la même œuvre tant que le signalement est ouvert.
        $alreadyOpen = $user && $work->reports()->where('user_id', $user->id)->where('status', Report::OPEN)->exists();

        if (! $alreadyOpen) {
            $work->reports()->create([
                'user_id' => $user?->id,
                'reason' => $data['reason'],
                'message' => $data['message'] ?? null,
                'email' => $user ? null : ($data['email'] ?? null),
            ]);

            AdminNotifier::send($work instanceof Illustration || $work instanceof Document
                ? MemberActivity::reported($work, Report::REASONS[$data['reason']])
                : new MemberActivity([
                    'kind' => MemberActivity::REPORTED,
                    'message' => '« '.$work->title.' » a été signalé : '.mb_strtolower(Report::REASONS[$data['reason']]).'.',
                    'url' => route('admin.reports.index'),
                ]));
        }

        return back()->with('report_sent', true);
    }
}
