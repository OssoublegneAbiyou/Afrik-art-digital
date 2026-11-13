<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\FeaturedSelection;
use App\Models\Illustration;
use App\Models\User;
use App\Models\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Mises en avant de l'accueil : « Artiste du jour », « Plume du jour » et œuvres épinglées.
 *
 * Ordre de priorité pour choisir le profil du jour :
 *   1. Planification datée (table featured_selections, écran Mises en avant › Planning) pour aujourd'hui ;
 *   2. Mode manuel (réglages accueil.artiste_du_jour_mode / accueil.plume_du_jour_mode = « manuel »),
 *      tant que le membre choisi a au moins une œuvre publiée — sinon on retombe sur le mode automatique ;
 *   3. Rotation automatique quotidienne : membres uniquement (pas l'équipe), avec au moins une œuvre publiée,
 *      et jamais la même personne deux jours de suite (le profil de la veille est mémorisé en cache),
 *      sauf s'il est le seul profil éligible.
 */
class Featured
{
    public const ARTIST = 'artist';
    public const WRITER = 'writer';

    // Préfixe des réglages par type de profil.
    private const SETTING = [self::ARTIST => 'accueil.artiste_du_jour', self::WRITER => 'accueil.plume_du_jour'];

    public static function artist(Collection $artists): ?Artist
    {
        return self::resolve(self::ARTIST, $artists);
    }

    public static function writer(Collection $writers): ?Writer
    {
        return self::resolve(self::WRITER, $writers);
    }

    /**
     * Sélection planifiée pour aujourd'hui (une par jour au plus).
     */
    public static function todaySelection(): ?FeaturedSelection
    {
        return FeaturedSelection::with(['artist.user', 'artist.illustrations', 'writer.user', 'writer.documents'])
            ->whereDate('featured_for', today())
            ->first();
    }

    /**
     * Profils qu'on peut choisir en mode manuel : au moins une œuvre publiée.
     */
    public static function candidates(string $kind): Collection
    {
        $query = $kind === self::ARTIST
            ? Artist::with('user')->has('illustrations')->whereHas('user', fn ($q) => $q->where('account_type', 'artist'))
            : Writer::with('user')->has('documents')->whereHas('user', fn ($q) => $q->where('account_type', 'writer'));

        return $query->get()->sortBy(fn ($profile) => mb_strtolower((string) $profile->user?->name))->values();
    }

    public static function mode(string $kind): string
    {
        return Settings::get(self::SETTING[$kind].'_mode') === 'manuel' ? 'manuel' : 'auto';
    }

    public static function manualId(string $kind): ?int
    {
        $id = Settings::get(self::SETTING[$kind].'_id');

        return $id ? (int) $id : null;
    }

    /**
     * Illustrations épinglées encore visibles, dans l'ordre choisi par l'équipe.
     */
    public static function pinnedIllustrations(): Collection
    {
        $ids = self::pinnedIds();

        if ($ids === []) {
            return collect();
        }

        return Illustration::with('artist.user')
            ->whereIn('id', $ids)
            ->whereHas('artist.user', fn ($query) => $query->where('account_type', 'artist'))
            ->get()
            ->sortBy(fn (Illustration $illustration) => array_search($illustration->id, $ids, true))
            ->values();
    }

    /** @return int[] */
    public static function pinnedIds(): array
    {
        return array_values(array_unique(array_map('intval', array_filter((array) Settings::get('accueil.oeuvres_epinglees', []), 'is_numeric'))));
    }

    // ---------- Interne ----------

    private static function resolve(string $kind, Collection $profiles): Artist|Writer|null
    {
        // 1. Planification datée.
        $selection = self::todaySelection();
        $planned = $kind === self::ARTIST ? $selection?->artist : $selection?->writer;

        // 2. Mode manuel, si le membre choisi a encore une œuvre publiée.
        if (! $planned && self::mode($kind) === 'manuel' && ($id = self::manualId($kind))) {
            $chosen = $profiles->first(fn ($profile) => (int) $profile->id === $id);
            $planned = $chosen && self::works($chosen)->isNotEmpty() ? $chosen : null;
        }

        // 3. Rotation automatique.
        $profile = $planned ?: self::rotate($kind, $profiles);

        if ($profile) {
            self::remember($kind, $profile->id);
            FeaturedNotifier::notify($profile);
        }

        return $profile;
    }

    private static function rotate(string $kind, Collection $profiles): Artist|Writer|null
    {
        $eligible = $profiles
            ->filter(fn ($profile) => $profile->user
                && $profile->user->role === User::ROLE_MEMBER
                && ! $profile->user->isDeactivated()
                && self::works($profile)->isNotEmpty())
            ->sortBy('id')
            ->values();

        if ($eligible->isEmpty()) {
            return null;
        }

        // Déjà tiré aujourd'hui : on garde le même profil toute la journée.
        $state = self::state($kind);
        if (($state['date'] ?? null) === today()->toDateString()) {
            $current = $eligible->first(fn ($profile) => (int) $profile->id === (int) ($state['id'] ?? 0));

            if ($current) {
                return $current;
            }
        }

        $previous = self::previousId($kind);
        $candidates = $eligible->count() > 1
            ? $eligible->reject(fn ($profile) => (int) $profile->id === $previous)->values()
            : $eligible;

        return $candidates->get(now()->dayOfYear % $candidates->count());
    }

    private static function works(Artist|Writer $profile): Collection
    {
        return $profile instanceof Artist ? $profile->illustrations : $profile->documents;
    }

    private static function key(string $kind): string
    {
        return 'featured-daily:'.$kind;
    }

    private static function state(string $kind): array
    {
        $state = Cache::get(self::key($kind));

        return is_array($state) ? $state : [];
    }

    /**
     * Profil mis en avant la veille (null si personne ou si le site n'a pas été visité hier).
     */
    private static function previousId(string $kind): ?int
    {
        $state = self::state($kind);

        $previous = match ($state['date'] ?? null) {
            today()->toDateString() => $state['previous'] ?? null,
            today()->subDay()->toDateString() => $state['id'] ?? null,
            default => null,
        };

        return $previous !== null ? (int) $previous : null;
    }

    /**
     * Mémorise le profil affiché aujourd'hui (et celui de la veille) pour la rotation du lendemain.
     */
    private static function remember(string $kind, int $id): void
    {
        $state = self::state($kind);

        if (($state['date'] ?? null) === today()->toDateString() && (int) ($state['id'] ?? 0) === $id) {
            return;
        }

        Cache::put(self::key($kind), [
            'date' => today()->toDateString(),
            'id' => $id,
            'previous' => self::previousId($kind),
        ], now()->addDays(7));
    }
}
