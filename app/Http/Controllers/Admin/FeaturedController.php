<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\FeaturedSelection;
use App\Models\Illustration;
use App\Models\Writer;
use App\Support\AdminLogger;
use App\Support\Featured;
use App\Support\FeaturedNotifier;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Écran « Mises en avant » : artiste / plume du jour (auto ou manuel), planification par date
 * et œuvres épinglées. Ordre de priorité côté public : voir App\Support\Featured.
 */
class FeaturedController extends Controller
{
    public const MAX_PINNED = 12;

    public function index(): View
    {
        $pinned = Featured::pinnedIllustrations();

        return view('admin.featured.index', [
            'artists' => Featured::candidates(Featured::ARTIST),
            'writers' => Featured::candidates(Featured::WRITER),
            'allArtists' => Artist::with('user')->whereHas('user', fn ($q) => $q->where('account_type', 'artist'))->get()
                ->sortBy(fn ($a) => mb_strtolower((string) $a->user?->name))->values(),
            'allWriters' => Writer::with('user')->whereHas('user', fn ($q) => $q->where('account_type', 'writer'))->get()
                ->sortBy(fn ($w) => mb_strtolower((string) $w->user?->name))->values(),
            'artistMode' => Featured::mode(Featured::ARTIST),
            'artistId' => Featured::manualId(Featured::ARTIST),
            'writerMode' => Featured::mode(Featured::WRITER),
            'writerId' => Featured::manualId(Featured::WRITER),
            'pinned' => $pinned,
            'illustrations' => Illustration::with('artist.user')
                ->whereHas('artist.user', fn ($q) => $q->where('account_type', 'artist'))
                ->latest()->take(300)->get(),
            'upcoming' => FeaturedSelection::with(['artist.user', 'writer.user'])
                ->whereDate('featured_for', '>=', today())->orderBy('featured_for')->get(),
            'maxPinned' => self::MAX_PINNED,
        ]);
    }

    /**
     * Mode automatique / manuel de l'artiste et de la plume du jour.
     */
    public function update(Request $request): RedirectResponse
    {
        $artistIds = Featured::candidates(Featured::ARTIST)->pluck('id')->all();
        $writerIds = Featured::candidates(Featured::WRITER)->pluck('id')->all();

        $data = $request->validate([
            'artiste_mode' => ['required', 'in:auto,manuel'],
            'artiste_id' => ['nullable', 'required_if:artiste_mode,manuel', 'integer', Rule::in($artistIds)],
            'plume_mode' => ['required', 'in:auto,manuel'],
            'plume_id' => ['nullable', 'required_if:plume_mode,manuel', 'integer', Rule::in($writerIds)],
        ], [
            'artiste_id.required_if' => 'Choisis l’artiste à mettre en avant, ou repasse en tirage automatique.',
            'artiste_id.in' => 'Cet artiste n’a pas d’œuvre publiée : il ne peut pas être mis en avant.',
            'plume_id.required_if' => 'Choisis la plume à mettre en avant, ou repasse en tirage automatique.',
            'plume_id.in' => 'Cette plume n’a pas de texte publié : elle ne peut pas être mise en avant.',
            '*.in' => 'Choix invalide.',
        ]);

        $values = [
            'accueil.artiste_du_jour_mode' => $data['artiste_mode'],
            'accueil.artiste_du_jour_id' => $data['artiste_mode'] === 'manuel' ? (int) $data['artiste_id'] : null,
            'accueil.plume_du_jour_mode' => $data['plume_mode'],
            'accueil.plume_du_jour_id' => $data['plume_mode'] === 'manuel' ? (int) $data['plume_id'] : null,
        ];

        $before = [];
        foreach ($values as $key => $value) {
            $before[$key] = Settings::set($key, $value);
        }

        AdminLogger::log('mise_en_avant.modifiee', 'setting', 'accueil.du_jour', $before, $values);

        // Choix manuel effectif dès aujourd'hui (sauf planification datée) : le membre est prévenu tout de suite.
        $today = FeaturedSelection::whereDate('featured_for', today())->first();
        if ($values['accueil.artiste_du_jour_id'] && ! $today?->artist_id) {
            FeaturedNotifier::notify(Artist::with('user')->find($values['accueil.artiste_du_jour_id']));
        }
        if ($values['accueil.plume_du_jour_id'] && ! $today?->writer_id) {
            FeaturedNotifier::notify(Writer::with('user')->find($values['accueil.plume_du_jour_id']));
        }

        return redirect()->route('admin.featured.index')->with('status', 'Artiste et plume du jour enregistrés.');
    }

    /**
     * Œuvres épinglées : liste ordonnée d'illustrations publiées.
     */
    public function pinned(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pinned' => ['nullable', 'array', 'max:'.self::MAX_PINNED],
            'pinned.*' => ['integer', 'distinct'],
        ], [
            'pinned.max' => 'Tu peux épingler au plus '.self::MAX_PINNED.' œuvres.',
            'pinned.*.distinct' => 'Une même œuvre est épinglée deux fois.',
            'pinned.*.integer' => 'Œuvre invalide.',
        ]);

        $ids = array_map('intval', $data['pinned'] ?? []);
        $published = Illustration::whereIn('id', $ids)->pluck('id')->all();

        if (count($published) !== count($ids)) {
            return back()->withErrors(['pinned' => 'Seules les illustrations publiées peuvent être épinglées.'])->withInput();
        }

        $before = Settings::set('accueil.oeuvres_epinglees', $ids);
        AdminLogger::log('mise_en_avant.modifiee', 'setting', 'accueil.oeuvres_epinglees', ['valeur' => $before], ['valeur' => $ids]);

        return redirect()->route('admin.featured.index')->with('status', $ids
            ? 'Œuvres épinglées enregistrées.'
            : 'Plus aucune œuvre épinglée.');
    }

    /**
     * Planification par date (prioritaire sur les modes auto / manuel ce jour-là).
     */
    public function schedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(60)->toDateString()],
            'artist_id' => ['nullable', 'exists:artists,id'],
            'writer_id' => ['nullable', 'exists:writers,id'],
        ], [
            'date.date' => 'Date invalide.',
            'date.after_or_equal' => 'Choisis aujourd’hui ou une date à venir.',
            'date.before_or_equal' => 'La mise en avant se planifie jusqu’à 60 jours à l’avance.',
            'artist_id.exists' => 'Artiste introuvable.',
            'writer_id.exists' => 'Plume introuvable.',
        ]);

        $date = isset($data['date']) ? Carbon::parse($data['date']) : today();
        $label = $date->locale('fr')->translatedFormat('l j F');

        // Rien de choisi : ce jour revient aux réglages auto / manuel.
        if (empty($data['artist_id']) && empty($data['writer_id'])) {
            $existing = FeaturedSelection::whereDate('featured_for', $date)->first();
            $existing?->delete();
            AdminLogger::log('mise_en_avant.modifiee', 'featured_selection', $existing?->id, $existing ? $this->snapshot($existing) : null, ['date' => $date->toDateString()]);

            return redirect()->route('admin.featured.index')->with('status', 'Le '.$label.' n’a plus de planification.');
        }

        $selection = FeaturedSelection::whereDate('featured_for', $date)->first() ?? new FeaturedSelection(['featured_for' => $date->toDateString()]);
        $before = $selection->exists ? $this->snapshot($selection) : null;
        $selection->fill(['artist_id' => $data['artist_id'] ?? null, 'writer_id' => $data['writer_id'] ?? null])->save();
        AdminLogger::log('mise_en_avant.modifiee', $selection, null, $before, $this->snapshot($selection));

        // Prévenir tout de suite si c'est pour aujourd'hui ; sinon, le jour venu (à la première visite).
        if ($date->isToday()) {
            FeaturedNotifier::notify($selection->artist()->with('user')->first());
            FeaturedNotifier::notify($selection->writer()->with('user')->first());
        }

        return redirect()->route('admin.featured.index')->with('status', $date->isToday()
            ? 'Mise en avant d’aujourd’hui planifiée.'
            : 'Mise en avant planifiée pour le '.$label.'.');
    }

    public function destroy(FeaturedSelection $selection): RedirectResponse
    {
        $before = $this->snapshot($selection);
        $selection->delete();
        AdminLogger::log('mise_en_avant.modifiee', $selection, null, $before, null);

        return redirect()->route('admin.featured.index')->with('status', 'Planification retirée : ce jour suit à nouveau les réglages auto / manuel.');
    }

    private function snapshot(FeaturedSelection $selection): array
    {
        return [
            'date' => $selection->featured_for?->toDateString(),
            'artist_id' => $selection->artist_id,
            'writer_id' => $selection->writer_id,
        ];
    }
}
