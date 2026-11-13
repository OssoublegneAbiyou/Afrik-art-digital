<?php

namespace App\Support;

use App\Models\AdminLog;
use Illuminate\Support\Str;

/**
 * Mise en forme lisible des entrées du journal : libellés d'action, cible, différences avant → après.
 */
class AdminLogPresenter
{
    public const ACTIONS = [
        'reglage.modifie' => 'Réglage modifié',
        'role.modifie' => 'Rôle modifié',
        'compte.cree' => 'Compte créé',
        'compte.desactive' => 'Compte désactivé',
        'compte.reactive' => 'Compte réactivé',
        'compte.supprime' => 'Compte supprimé',
        'oeuvre.validee' => 'Œuvre validée',
        'oeuvre.refusee' => 'Œuvre refusée',
        'signalement.classe' => 'Signalement classé',
        'signalement.oeuvre_retiree' => 'Œuvre retirée après signalement',
        'demande.acceptee' => 'Demande d’adhésion acceptée',
        'demande.refusee' => 'Demande d’adhésion refusée',
        'demande.note' => 'Note interne modifiée',
        'page.modifiee' => 'Page modifiée',
        'annonce.modifiee' => 'Annonce modifiée',
        'genre.modifie' => 'Genre modifié',
        'type.modifie' => 'Type modifié',
        'mise_en_avant.modifiee' => 'Mise en avant modifiée',
    ];

    private const TARGETS = [
        'user' => 'Compte',
        'setting' => 'Réglage',
        'illustration' => 'Illustration',
        'document' => 'Récit',
        'report' => 'Signalement',
        'join_request' => 'Demande',
    ];

    public static function actionLabel(string $action): string
    {
        return self::ACTIONS[$action] ?? Str::of($action)->replace(['.', '_'], ' ')->ucfirst()->toString();
    }

    public static function target(AdminLog $log): ?string
    {
        if (! $log->cible_type) {
            return null;
        }

        $type = class_exists($log->cible_type) ? Str::snake(class_basename($log->cible_type)) : $log->cible_type;
        $label = self::TARGETS[$type] ?? Str::ucfirst(str_replace('_', ' ', $type));

        if ($log->cible_id === null || $log->cible_id === '') {
            return $label;
        }

        return $label.(is_numeric($log->cible_id) ? ' n° ' : ' ').$log->cible_id;
    }

    /**
     * @return array<int, array{key: string, before: ?string, after: ?string}>
     */
    public static function changes(AdminLog $log, int $limit = 160): array
    {
        $before = is_array($log->avant) ? $log->avant : [];
        $after = is_array($log->apres) ? $log->apres : [];

        return collect(array_unique(array_merge(array_keys($before), array_keys($after))))
            ->map(fn ($key) => [
                'key' => (string) $key,
                'before' => array_key_exists($key, $before) ? self::format($before[$key], $limit) : null,
                'after' => array_key_exists($key, $after) ? self::format($after[$key], $limit) : null,
            ])
            ->values()
            ->all();
    }

    public static function format(mixed $value, int $limit = 160): string
    {
        $text = match (true) {
            $value === null, $value === '' => '(vide)',
            $value === true => 'oui',
            $value === false => 'non',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };

        return Str::limit($text, $limit, ' …');
    }
}
