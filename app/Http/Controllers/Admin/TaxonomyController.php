<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Genre;
use App\Models\Illustration;
use App\Models\IllustrationType;
use App\Support\AdminLogger;
use App\Support\Fr;
use App\Support\SongeSky;
use App\Support\SvgSanitizer;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin › Types & genres : types d'illustration (galerie, envoi) et genres littéraires (ciels Songe).
 * Le slug est calculé à la création puis figé (il est stocké dans les œuvres).
 * Un type ou un genre utilisé ne se supprime pas : on le désactive.
 */
class TaxonomyController extends Controller
{
    // Couleurs : #RRGGBB.
    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    private const COLOR_FIELDS = [
        'ciel_jour' => 'ciel de jour',
        'encre_jour' => 'encre de jour',
        'ciel_nuit' => 'ciel de nuit',
        'encre_nuit' => 'encre de nuit',
        'lueur_nuit' => 'lueur de nuit',
    ];

    public function index(): View
    {
        $typeUsage = Illustration::withUnpublished()->selectRaw('type, count(*) as total')->whereNotNull('type')->groupBy('type')->pluck('total', 'type');
        $genreUsage = Document::withUnpublished()->selectRaw('genre, count(*) as total')->whereNotNull('genre')->groupBy('genre')->pluck('total', 'genre');

        return view('admin.taxonomy.index', [
            'types' => IllustrationType::query()->ordonne()->get(),
            'genres' => Genre::query()->ordonne()->get(),
            'typeUsage' => $typeUsage,
            'genreUsage' => $genreUsage,
            // Œuvres rangées dans « Autre » : sans type, type « autre », désactivé ou inconnu.
            'otherCount' => Illustration::withUnpublished()
                ->where(fn ($q) => $q->whereNull('type')->orWhereNotIn('type', IllustrationType::query()->actif()->pluck('slug')))
                ->count(),
        ]);
    }

    // ---------- Types d'illustration ----------

    public function storeType(Request $request): RedirectResponse
    {
        $data = $this->validateType($request, null);

        $type = IllustrationType::create([
            'slug' => $this->uniqueSlug($data['nom'], IllustrationType::class, [Illustration::OTHER_TYPE], 'type'),
            'nom' => $data['nom'],
            'ordre' => $data['ordre'] ?? ((int) IllustrationType::max('ordre') + 1),
            'actif' => $request->boolean('actif', true),
        ]);
        $this->renumber(IllustrationType::class);

        AdminLogger::log('type.cree', $type, null, null, $this->snapshot($type->fresh()));

        return redirect()->route('admin.taxonomy.index')->with('status', 'Type « '.$type->nom.' » ajouté.');
    }

    public function updateType(Request $request, IllustrationType $illustrationType): RedirectResponse
    {
        $data = $this->validateType($request, $illustrationType);
        $before = $this->snapshot($illustrationType);

        $illustrationType->update([
            'nom' => $data['nom'],
            'ordre' => $data['ordre'] ?? $illustrationType->ordre,
            'actif' => $request->boolean('actif'),
        ]);
        $this->renumber(IllustrationType::class, $illustrationType);

        AdminLogger::log('type.modifie', $illustrationType, null, $before, $this->snapshot($illustrationType->fresh()));

        return redirect()->route('admin.taxonomy.index')->with('status', 'Type « '.$illustrationType->nom.' » enregistré.');
    }

    public function moveType(Request $request, IllustrationType $illustrationType): RedirectResponse
    {
        $before = $this->snapshot($illustrationType);
        $this->move(IllustrationType::class, $illustrationType, $request->input('direction'));

        AdminLogger::log('type.modifie', $illustrationType, null, $before, $this->snapshot($illustrationType->fresh()));

        return redirect()->route('admin.taxonomy.index')->with('status', 'Ordre des types enregistré.');
    }

    public function destroyType(IllustrationType $illustrationType): RedirectResponse
    {
        $used = $illustrationType->usageCount();

        if ($used > 0) {
            return redirect()->route('admin.taxonomy.index')->with('error', 'Ce type est utilisé par '.Fr::count($used, 'illustration').' : désactivez-le plutôt.');
        }

        $before = $this->snapshot($illustrationType);
        $illustrationType->delete();
        $this->renumber(IllustrationType::class);

        AdminLogger::log('type.supprime', 'illustration_type', $illustrationType->id, $before, null);

        return redirect()->route('admin.taxonomy.index')->with('status', 'Type « '.$before['nom'].' » supprimé.');
    }

    // ---------- Genres ----------

    public function createGenre(): View
    {
        return view('admin.taxonomy.genre-form', ['genre' => null, 'usage' => 0]);
    }

    public function storeGenre(Request $request): RedirectResponse
    {
        $data = $this->validateGenre($request, null);

        $genre = Genre::create($data + [
            'slug' => $this->uniqueSlug($data['nom'], Genre::class, ['all'], 'genre'),
            'ordre' => (int) Genre::max('ordre') + 1,
        ]);
        $this->renumber(Genre::class, $genre);

        AdminLogger::log('genre.cree', $genre, null, null, $this->snapshot($genre->fresh()));

        return redirect()->route('admin.taxonomy.genres.edit', $genre)
            ->with('status', 'Genre « '.$genre->nom.' » ajouté : il est proposé aux écrivains'.($genre->actif ? '' : ' dès qu’il sera activé').'.'.$this->emblemNotice($request));
    }

    public function editGenre(Genre $genre): View
    {
        return view('admin.taxonomy.genre-form', ['genre' => $genre, 'usage' => $genre->usageCount()]);
    }

    public function updateGenre(Request $request, Genre $genre): RedirectResponse
    {
        $data = $this->validateGenre($request, $genre);
        $before = $this->snapshot($genre);

        $genre->update($data);
        $this->renumber(Genre::class, $genre);

        AdminLogger::log('genre.modifie', $genre, null, $before, $this->snapshot($genre->fresh()));

        return redirect()->route('admin.taxonomy.genres.edit', $genre)
            ->with('status', 'Genre « '.$genre->nom.' » enregistré.'.$this->emblemNotice($request));
    }

    public function moveGenre(Request $request, Genre $genre): RedirectResponse
    {
        $before = $this->snapshot($genre);
        $this->move(Genre::class, $genre, $request->input('direction'));

        AdminLogger::log('genre.modifie', $genre, null, $before, $this->snapshot($genre->fresh()));

        return redirect()->route('admin.taxonomy.index')->with('status', 'Ordre des genres enregistré.');
    }

    public function destroyGenre(Genre $genre): RedirectResponse
    {
        $used = $genre->usageCount();

        if ($used > 0) {
            return back()->with('error', 'Ce genre est utilisé par '.Fr::count($used, 'récit').' : désactivez-le plutôt.');
        }

        $before = $this->snapshot($genre);
        $genre->delete();
        $this->renumber(Genre::class);

        AdminLogger::log('genre.supprime', 'genre', $genre->id, $before, null);

        return redirect()->route('admin.taxonomy.index')->with('status', 'Genre « '.$before['nom'].' » supprimé.');
    }

    /**
     * Aperçu de l'emblème tel qu'il sera affiché (assaini), pour le formulaire.
     */
    public function previewEmblem(Request $request): JsonResponse
    {
        $request->validate(['emblem' => ['nullable', 'string', 'max:20000']]);
        $parts = SvgSanitizer::parts($request->input('emblem'));

        return response()->json([
            'valid' => $parts !== null,
            'svg' => self::emblemSvg($parts),
        ]);
    }

    /**
     * Emblème prêt à afficher (trait, couleur héritée) ; livre ouvert si rien d'utilisable.
     */
    public static function emblemSvg(?array $parts): string
    {
        $parts ??= ['viewBox' => SvgSanitizer::DEFAULT_VIEWBOX, 'inner' => Genre::BOOK_EMBLEM];

        return '<svg viewBox="'.e($parts['viewBox']).'" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" width="96" height="96">'
            .$parts['inner'].'</svg>';
    }

    // ---------- Outils ----------

    private function validateType(Request $request, ?IllustrationType $type): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:60', Rule::unique('illustration_types', 'nom')->ignore($type?->id),
                function (string $attribute, mixed $value, Closure $fail) {
                    if (Str::slug((string) $value) === Illustration::OTHER_TYPE || mb_strtolower(trim((string) $value)) === 'autre') {
                        $fail('« Autre » existe déjà : c’est le choix de repli proposé à l’envoi et dans la galerie.');
                    }
                }],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'actif' => ['nullable', 'boolean'],
        ], [
            'nom.required' => 'Indiquez le nom du type.',
            'nom.max' => 'Le nom ne doit pas dépasser 60 caractères.',
            'nom.unique' => 'Un type porte déjà ce nom.',
            'ordre.integer' => 'L’ordre doit être un nombre entier.',
            'ordre.min' => 'L’ordre doit être positif.',
            'ordre.max' => 'L’ordre doit être inférieur à 10 000.',
        ]);
    }

    private function validateGenre(Request $request, ?Genre $genre): array
    {
        // Les couleurs saisies en minuscules ou sans « # » sont acceptées puis normalisées.
        $request->merge(collect(self::COLOR_FIELDS)->keys()->mapWithKeys(function ($field) use ($request) {
            $value = trim((string) $request->input($field));

            return [$field => preg_match('/^#?[0-9A-Fa-f]{6}$/', $value) ? '#'.strtoupper(ltrim($value, '#')) : $value];
        })->all());

        $sky = function (string $attribute, mixed $value, Closure $fail) {
            $value = (string) $value;

            if (! preg_match(self::HEX, $value) && ! SongeSky::isSafeGradient($value)) {
                $fail('Indiquez une couleur au format #RRGGBB (ex. #B79CF0).');
            }
        };

        $messages = [
            'nom.required' => 'Indiquez le nom du genre.',
            'nom.max' => 'Le nom ne doit pas dépasser 60 caractères.',
            'nom.unique' => 'Un genre porte déjà ce nom.',
            'phrase_accroche.max' => 'La phrase d’accroche ne doit pas dépasser 200 caractères.',
            'description.max' => 'La description ne doit pas dépasser 160 caractères.',
            'emblem.max' => 'L’emblème est trop long (20 000 caractères au maximum).',
            'ordre.integer' => 'L’ordre doit être un nombre entier.',
            'ordre.min' => 'L’ordre doit être positif.',
            'ordre.max' => 'L’ordre doit être inférieur à 10 000.',
        ];

        foreach (self::COLOR_FIELDS as $field => $label) {
            $messages[$field.'.required'] = 'Choisissez la couleur « '.$label.' ».';
            $messages[$field.'.regex'] = 'Indiquez une couleur au format #RRGGBB (ex. #2A2350).';
        }

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:60', Rule::unique('genres', 'nom')->ignore($genre?->id)],
            'phrase_accroche' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:160'],
            'ciel_jour' => ['required', 'string', 'max:160', $sky],
            'encre_jour' => ['required', 'regex:'.self::HEX],
            'ciel_nuit' => ['required', 'string', 'max:160', $sky],
            'encre_nuit' => ['required', 'regex:'.self::HEX],
            'lueur_nuit' => ['required', 'regex:'.self::HEX],
            'emblem' => ['nullable', 'string', 'max:20000', function (string $attribute, mixed $value, Closure $fail) {
                if (filled($value) && SvgSanitizer::clean((string) $value) === null) {
                    $fail('Cet emblème ne contient aucune forme utilisable (path, circle, ellipse, rect, line, polyline, polygon, g). Laissez le champ vide pour le livre ouvert.');
                }
            }],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'actif' => ['nullable', 'boolean'],
        ], $messages);

        return [
            'nom' => trim($data['nom']),
            'phrase_accroche' => filled($data['phrase_accroche'] ?? null) ? trim($data['phrase_accroche']) : null,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'ciel_jour' => $data['ciel_jour'],
            'encre_jour' => $data['encre_jour'],
            'ciel_nuit' => $data['ciel_nuit'],
            'encre_nuit' => $data['encre_nuit'],
            'lueur_nuit' => $data['lueur_nuit'],
            // Toujours enregistré assaini.
            'emblem' => filled($data['emblem'] ?? null) ? SvgSanitizer::clean($data['emblem']) : null,
            'actif' => $request->boolean('actif'),
        ] + (isset($data['ordre']) ? ['ordre' => (int) $data['ordre']] : []);
    }

    /**
     * Phrase ajoutée au message de succès quand des éléments interdits ont été retirés de l'emblème.
     */
    private function emblemNotice(Request $request): string
    {
        $raw = (string) $request->input('emblem');

        return preg_match('/<\s*(script|style|foreignObject|image|use|a|iframe|object|embed|text|animate|set)\b|\son[a-z]+\s*=|javascript:|href\s*=/i', $raw)
            ? ' Les éléments non autorisés de l’emblème (scripts, liens, styles…) ont été retirés.'
            : '';
    }

    /**
     * Slug unique tiré du nom (« Science-fiction » → science-fiction), jamais un slug réservé.
     *
     * @param  class-string<Model>  $model
     */
    private function uniqueSlug(string $name, string $model, array $reserved, string $fallback): string
    {
        $base = Str::limit(Str::slug($name), 50, '') ?: $fallback;
        $slug = $base;
        $i = 2;

        while (in_array($slug, $reserved, true) || $model::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * Monte ou descend d'un cran, puis renumérote 1, 2, 3…
     *
     * @param  class-string<Model>  $model
     */
    private function move(string $model, Model $item, ?string $direction): void
    {
        $items = $model::query()->ordonne()->get()->values();
        $index = $items->search(fn ($other) => $other->is($item));
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $target < 0 || $target >= $items->count()) {
            return;
        }

        $list = $items->all();
        [$list[$index], $list[$target]] = [$list[$target], $list[$index]];

        foreach (array_values($list) as $position => $other) {
            if ($other->ordre !== $position + 1) {
                $other->update(['ordre' => $position + 1]);
            }
        }
    }

    /**
     * Ordres consécutifs (1, 2, 3…). L'élément qui vient d'être placé passe avant ceux de même ordre.
     *
     * @param  class-string<Model>  $model
     */
    private function renumber(string $model, ?Model $priority = null): void
    {
        $items = $model::query()->get()->sortBy([
            fn ($a, $b) => $a->ordre <=> $b->ordre,
            fn ($a, $b) => ($priority && $b->is($priority)) <=> ($priority && $a->is($priority)),
            fn ($a, $b) => strcmp(mb_strtolower($a->nom), mb_strtolower($b->nom)),
        ])->values();

        foreach ($items as $position => $item) {
            if ($item->ordre !== $position + 1) {
                $item->update(['ordre' => $position + 1]);
            }
        }
    }

    private function snapshot(Model $item): array
    {
        return collect($item->only([
            'slug', 'nom', 'ordre', 'actif', 'phrase_accroche', 'description',
            'ciel_jour', 'encre_jour', 'ciel_nuit', 'encre_nuit', 'lueur_nuit', 'emblem',
        ]))->all();
    }
}
