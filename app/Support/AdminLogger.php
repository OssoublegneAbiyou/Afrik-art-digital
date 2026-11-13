<?php

namespace App\Support;

use App\Models\AdminLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Journalise une action d'administration : réglage modifié, rôle changé, œuvre validée ou refusée,
 * signalement ou demande d'adhésion traités…
 *
 *   AdminLogger::log('reglage.modifie', 'setting', 'contact.email', ['valeur' => $avant], ['valeur' => $apres]);
 *   AdminLogger::log('oeuvre.validee', $illustration);
 *
 * Actions conseillées (verbe au participe passé) : reglage.modifie, role.modifie, compte.cree,
 * compte.desactive, compte.reactive, compte.supprime, oeuvre.validee, oeuvre.refusee, signalement.classe,
 * signalement.oeuvre_retiree, demande.acceptee, demande.refusee, page.modifiee, annonce.modifiee,
 * genre.modifie, type.modifie, mise_en_avant.modifiee.
 * Un échec d'écriture n'interrompt jamais l'action elle-même.
 */
class AdminLogger
{
    /**
     * @param  Model|string|null  $target  modèle concerné, ou type libre (ex. « setting »)
     */
    public static function log(string $action, Model|string|null $target = null, mixed $targetId = null, ?array $before = null, ?array $after = null): void
    {
        try {
            [$type, $id] = $target instanceof Model
                ? [$target->getMorphClass(), (string) $target->getKey()]
                : [$target, $targetId !== null ? (string) $targetId : null];

            AdminLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'cible_type' => $type,
                'cible_id' => $id,
                'avant' => $before,
                'apres' => $after,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Admin log failed', ['action' => $action, 'error' => $exception->getMessage()]);
        }
    }
}
