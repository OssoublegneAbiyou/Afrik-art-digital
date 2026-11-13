<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Support\AdminLogger;
use App\Support\RichText;
use App\Support\Settings;
use App\Support\SocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * Admin › Réglages du collectif. Un onglet = un formulaire qui n'enregistre que ses propres champs.
 * Les champs sont générés depuis le catalogue config/collectif.php (les réglages « accueil.* »
 * sont gérés par l'écran « Mises en avant »).
 */
class SettingsController extends Controller
{
    /** Taille maximale d'une image de réglage, en Ko. */
    public const IMAGE_MAX_KB = 5120;

    /**
     * Onglets : libellé, groupes du catalogue affichés, page publique où voir le résultat.
     */
    public const TABS = [
        'identite' => ['label' => 'Identité', 'groups' => ['identite'], 'link' => 'public.index'],
        'presentation' => ['label' => 'Présentation', 'groups' => ['presentation'], 'link' => 'public.index'],
        'contact' => ['label' => 'Contact & réseaux', 'groups' => ['contact', 'reseaux'], 'link' => 'public.about', 'anchor' => 'contact'],
        'adhesion' => ['label' => 'Adhésion', 'groups' => ['adhesion'], 'link' => 'public.about', 'anchor' => 'rejoindre'],
        'decor' => ['label' => 'Décor', 'groups' => ['decor'], 'link' => 'public.illustrations'],
        'regles' => ['label' => 'Règles', 'groups' => ['regles'], 'link' => null],
        'bd' => ['label' => 'Récits dessinés', 'groups' => ['bd'], 'link' => null],
        'seo' => ['label' => 'SEO', 'groups' => ['seo'], 'link' => 'public.index'],
        'apparence' => ['label' => 'Apparence', 'groups' => [], 'link' => 'dashboard'],
    ];

    public function edit(Request $request): View
    {
        $tab = $this->tab($request->query('onglet'));

        $fields = [];
        foreach ($this->keysFor($tab) as $key) {
            $definition = Settings::definition($key);
            $value = Settings::get($key);

            $fields[] = [
                'key' => $key,
                'name' => self::fieldName($key),
                'definition' => $definition,
                'value' => $value,
                'image_url' => $definition['type'] === 'image' ? Settings::imageUrl($key) : null,
            ];
        }

        return view('admin.settings.edit', [
            'tabs' => self::TABS,
            'tab' => $tab,
            'fields' => $fields,
            'dashboardThemes' => $tab === 'apparence' ? PlatformSetting::dashboardThemes() : [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tab = $this->tab($request->input('onglet'));
        $back = route('admin.settings.edit', ['onglet' => $tab]);

        if ($tab === 'apparence') {
            return $this->updateThemes($request, $back);
        }

        $keys = $this->keysFor($tab);

        // Bouton « Retirer » d'une image (formulaire séparé : rien d'autre n'est enregistré).
        if ($request->filled('retirer')) {
            $key = $this->keyFromName((string) $request->input('retirer'));
            abort_unless($key && in_array($key, $keys, true) && Settings::definition($key)['type'] === 'image', 422);

            $this->saveImage($key, null);

            return redirect()->to($back)->with('status', '« '.Settings::definition($key)['label'].' » retiré.');
        }

        $values = $this->validateTab($request, $keys, $back);

        $changed = 0;
        foreach ($values as $key => $value) {
            $type = Settings::definition($key)['type'];

            if ($type === 'image') {
                $this->saveImage($key, $value);
                $changed++;

                continue;
            }

            $before = Settings::get($key);

            if (Settings::serialize($before, $type) === Settings::serialize($value, $type)) {
                continue;
            }

            Settings::set($key, $value);
            AdminLogger::log('reglage.modifie', 'setting', $key, ['valeur' => $before], ['valeur' => Settings::get($key)]);
            $changed++;
        }

        $label = self::TABS[$tab]['label'];

        return redirect()->to($back)->with('status', $changed
            ? 'Réglages « '.$label.' » enregistrés.'
            : 'Aucun changement dans « '.$label.' ».');
    }

    /** Nom de champ HTML d'un réglage : les points gêneraient la validation (« contact.email » → « contact__email »). */
    public static function fieldName(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    // ---------- Interne ----------

    private function tab(?string $tab): string
    {
        return array_key_exists((string) $tab, self::TABS) ? $tab : array_key_first(self::TABS);
    }

    /** @return list<string> */
    private function keysFor(string $tab): array
    {
        $groups = self::TABS[$tab]['groups'];

        return collect(Settings::definitions())
            ->filter(fn ($definition) => in_array($definition['group'], $groups, true))
            ->sortBy(fn ($definition) => array_search($definition['group'], $groups, true), SORT_REGULAR, false)
            ->keys()
            ->all();
    }

    private function keyFromName(string $name): ?string
    {
        $key = str_replace('__', '.', $name);

        return Settings::has($key) ? $key : null;
    }

    /**
     * Valide les champs de l'onglet et renvoie les valeurs prêtes à enregistrer (clé => valeur).
     * Les images ne figurent dans le résultat que si un fichier a été envoyé.
     *
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function validateTab(Request $request, array $keys, string $back): array
    {
        $rules = [];
        $attributes = [];
        $lists = [];

        foreach ($keys as $key) {
            $definition = Settings::definition($key);
            $name = self::fieldName($key);
            $attributes[$name] = '« '.$definition['label'].' »';
            $presence = ! empty($definition['required']) ? 'required' : 'nullable';
            $max = $definition['max'] ?? null;

            $rules[$name] = match ($definition['type']) {
                'text', 'textarea', 'richtext' => array_filter([$presence, 'string', $max ? 'max:'.$max : 'max:5000']),
                'email' => [$presence, 'string', 'email', 'max:255'],
                'phone' => [$presence, 'string', 'max:30', 'regex:/^\+?[0-9][0-9 .()\-]{5,28}$/'],
                'url' => [$presence, 'string', 'max:255'],
                'integer' => [$presence, 'integer', 'min:'.($definition['min'] ?? 0), 'max:'.($definition['max_value'] ?? PHP_INT_MAX)],
                'boolean' => ['nullable', 'boolean'],
                'select' => ['required', Rule::in(array_keys($definition['options'] ?? []))],
                'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::IMAGE_MAX_KB],
                'json' => ['nullable', 'string', 'max:2000'],
                default => ['nullable', 'string', 'max:5000'],
            };

            if ($definition['type'] === 'json') {
                $lists[$key] = $name;
            }
        }

        $messages = [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être un texte.',
            'max.string' => ':attribute : :max caractères au plus.',
            'max.numeric' => ':attribute : :max au plus.',
            'min.numeric' => ':attribute : :min au moins.',
            'max.file' => ':attribute : l’image ne doit pas dépasser '.(self::IMAGE_MAX_KB / 1024).' Mo.',
            'email' => 'Cette adresse e-mail ne semble pas valide.',
            'integer' => ':attribute doit être un nombre entier.',
            'regex' => 'Ce numéro ne semble pas valide : chiffres, espaces et « + » seulement (ex. +225 07 00 00 00 00).',
            'in' => 'Choisis une des options proposées.',
            'boolean' => 'Valeur invalide.',
            'file' => 'Le fichier n’a pas pu être envoyé.',
            'image' => ':attribute doit être une image JPG, PNG ou WEBP.',
            'mimes' => ':attribute doit être une image JPG, PNG ou WEBP (SVG non accepté).',
        ];

        $validator = validator($request->all(), $rules, $messages, $attributes);

        // Réseaux sociaux : « @nom » ou adresse complète, normalisés en URL https.
        // Listes (décor) : une phrase par ligne, longueur et nombre limités.
        $validator->after(function (Validator $validator) use ($request, $keys, $lists) {
            foreach ($keys as $key) {
                $definition = Settings::definition($key);
                $name = self::fieldName($key);
                $raw = $request->input($name);

                if ($definition['type'] === 'url' && filled($raw) && ! $validator->errors()->has($name)
                    && $this->normalizeUrl($definition, (string) $raw) === null) {
                    $validator->errors()->add($name, $definition['group'] === 'reseaux'
                        ? 'Indique une adresse complète (https://…) ou un identifiant (@nom).'
                        : 'Indique une adresse complète commençant par https://.');
                }
            }

            foreach ($lists as $key => $name) {
                $definition = Settings::definition($key);
                $items = $this->lines($request->input($name));
                $itemMax = $definition['items_max'] ?? 40;

                foreach ($items as $index => $item) {
                    if (mb_strlen($item) > $itemMax) {
                        $validator->errors()->add($name, 'Ligne '.($index + 1).' : '.$itemMax.' caractères au plus (« '.Str::limit($item, 24).' » en compte '.mb_strlen($item).').');
                    }
                }

                if (isset($definition['items_count']) && count($items) !== $definition['items_count']) {
                    $validator->errors()->add($name, 'Il faut exactement '.$definition['items_count'].' lignes.');
                } elseif (isset($definition['items_count_max']) && count($items) > $definition['items_count_max']) {
                    $validator->errors()->add($name, $definition['items_count_max'].' lignes au plus.');
                }
            }
        });

        // Retour toujours sur l'onglet concerné (pas sur l'URL « précédente »).
        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo($back);
        }

        $values = [];
        foreach ($keys as $key) {
            $definition = Settings::definition($key);
            $name = self::fieldName($key);
            $input = $request->input($name);

            $values[$key] = match ($definition['type']) {
                'boolean' => $request->boolean($name),
                'integer' => filled($input) ? (int) $input : null,
                'json' => $this->lines($input),
                'richtext' => RichText::clean($input) ?: null,
                'url' => filled($input) ? $this->normalizeUrl($definition, (string) $input) : null,
                'email' => filled($input) ? Str::lower(trim($input)) : null,
                'image' => $request->file($name),
                default => filled($input) ? trim((string) $input) : null,
            };

            if ($definition['type'] === 'image' && ! $values[$key] instanceof UploadedFile) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    private function normalizeUrl(array $definition, string $value): ?string
    {
        $url = SocialLink::url($definition['network'] ?? 'website', $value);

        if ($url === null || ! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://[^/\s]+\.[^/\s]+#i', $url)) {
            return null;
        }

        // Toujours en https.
        return preg_replace('#^http://#i', 'https://', $url);
    }

    /** @return list<string> */
    private function lines(mixed $value): array
    {
        if (is_array($value)) {
            $value = implode("\n", $value);
        }

        return collect(preg_split('/\R/u', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();
    }

    /**
     * Enregistre (ou retire avec null) l'image d'un réglage ; l'ancien fichier est supprimé.
     */
    private function saveImage(string $key, ?UploadedFile $file): void
    {
        $before = Settings::get($key);
        $path = $file?->store('collectif', 'public');

        if ($before === $path) {
            return;
        }

        Settings::set($key, $path);

        if (filled($before) && Str::startsWith($before, 'collectif/')) {
            Storage::disk('public')->delete($before);
        }

        AdminLogger::log('reglage.modifie', 'setting', $key, ['valeur' => $before], ['valeur' => $path]);
    }

    private function updateThemes(Request $request, string $back): RedirectResponse
    {
        $types = array_keys(PlatformSetting::defaultDashboardThemes());
        $color = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];

        $rules = [];
        foreach ($types as $type) {
            // Couleur ou dégradé CSS simple : pas de « ; », d'accolades, de guillemets ni d'url().
            $rules["themes.$type.background"] = ['required', 'string', 'max:255', 'regex:/^[#a-zA-Z0-9(),.%\s-]+$/', 'not_regex:/url\s*\(/i'];
            $rules["themes.$type.panel"] = $color;
            $rules["themes.$type.text"] = $color;
            $rules["themes.$type.accent"] = $color;
        }

        $validator = validator($request->all(), $rules, [
            'required' => 'Ce champ est obligatoire.',
            'regex' => 'Valeur invalide : une couleur (#RRGGBB) ou un dégradé CSS simple.',
            'not_regex' => 'Les images (url(…)) ne sont pas acceptées.',
            'max' => '255 caractères au plus.',
        ]);

        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo($back);
        }

        $data = $validator->validated();

        $before = PlatformSetting::dashboardThemes();
        $after = $before;

        foreach ($types as $type) {
            $after[$type] = array_merge($after[$type], $data['themes'][$type]);
        }

        if ($after === $before) {
            return redirect()->to($back)->with('status', 'Aucun changement dans « Apparence ».');
        }

        PlatformSetting::updateDashboardThemes($after);
        AdminLogger::log('reglage.modifie', 'setting', 'dashboard_themes', ['valeur' => $before], ['valeur' => $after]);

        return redirect()->to($back)->with('status', 'Couleurs des tableaux de bord enregistrées.');
    }
}
