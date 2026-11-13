<?php

namespace App\Models\Concerns;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Brouillons, publication programmée (published_at) et validation par l'équipe (moderation_status).
 *
 * Par sécurité, une œuvre n'est visible publiquement que si elle est publiée ET validée : un scope global
 * l'applique PARTOUT par défaut, un oubli ne peut donc pas faire fuiter un brouillon ou une œuvre refusée.
 * Les espaces de l'auteur utilisent explicitement withUnpublished() / les relations « all… », et l'auteur
 * (ou un admin) peut ouvrir ses œuvres non visibles par leur URL.
 */
trait HasPublication
{
    public const STATUS_PUBLISHED = 'publie';
    public const STATUS_DRAFT = 'brouillon';
    public const STATUS_SCHEDULED = 'programme';

    public const MODERATION_APPROVED = 'approved';
    public const MODERATION_PENDING = 'pending';
    public const MODERATION_REJECTED = 'rejected';

    public static function bootHasPublication(): void
    {
        static::addGlobalScope('published', function (Builder $query) {
            $query->whereNotNull($query->qualifyColumn('published_at'))
                ->where($query->qualifyColumn('published_at'), '<=', now())
                ->where($query->qualifyColumn('moderation_status'), self::MODERATION_APPROVED);
        });
    }

    public function initializeHasPublication(): void
    {
        $this->mergeCasts([
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
            'rights_certified_at' => 'datetime',
        ]);

        // Valeurs par défaut d'une nouvelle œuvre : publiée immédiatement et validée (comportement historique).
        // Posées à l'initialisation, et non dans un événement « creating », pour rester vraies même avec
        // withoutEvents() (imports, scripts). Les contrôleurs passent explicitement en brouillon / « pending ».
        if (! $this->exists) {
            $this->attributes['published_at'] ??= $this->fromDateTime(now());
            $this->attributes['moderation_status'] ??= self::MODERATION_APPROVED;
        }
    }

    public static function withUnpublished(): Builder
    {
        return static::withoutGlobalScope('published');
    }

    /**
     * Statut de validation d'une nouvelle soumission : les admins publient directement ;
     * sinon « pending » quand la validation avant publication est activée.
     */
    public static function moderationStatusFor(?User $author): string
    {
        return $author?->isStaff() || ! PlatformSetting::moderationEnabled()
            ? self::MODERATION_APPROVED
            : self::MODERATION_PENDING;
    }

    // ---------- Date de publication ----------

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->published_at !== null && $this->published_at->isFuture();
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    public function publicationStatus(): string
    {
        return match (true) {
            $this->isDraft() => self::STATUS_DRAFT,
            $this->isScheduled() => self::STATUS_SCHEDULED,
            default => self::STATUS_PUBLISHED,
        };
    }

    public function publicationLabel(): string
    {
        return match ($this->publicationStatus()) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SCHEDULED => 'Programmée le '.$this->published_at->locale('fr')->translatedFormat('j F Y à H\hi'),
            default => 'Publiée',
        };
    }

    /**
     * Date de publication selon le choix du formulaire (« publie », « brouillon », « programme » + date).
     */
    public static function publicationDateFor(string $status, ?string $scheduledAt, ?Carbon $current = null): ?Carbon
    {
        return match ($status) {
            self::STATUS_DRAFT => null,
            self::STATUS_SCHEDULED => Carbon::parse($scheduledAt),
            // Déjà publiée : on garde sa date d'origine (ordre de la galerie inchangé).
            default => $current && $current->lte(now()) ? $current : now(),
        };
    }

    // ---------- Validation par l'équipe ----------

    public function isApproved(): bool
    {
        return ($this->moderation_status ?? self::MODERATION_APPROVED) === self::MODERATION_APPROVED;
    }

    public function isPendingReview(): bool
    {
        return $this->moderation_status === self::MODERATION_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->moderation_status === self::MODERATION_REJECTED;
    }

    /**
     * Visible par tout le monde : publiée et validée.
     */
    public function isVisible(): bool
    {
        return $this->isPublished() && $this->isApproved();
    }

    /**
     * Libellé complet pour l'auteur (date de publication + validation).
     */
    public function statusLabel(): string
    {
        return match (true) {
            $this->isDraft() => 'Brouillon',
            $this->isRejected() => 'Refusée par l’équipe',
            $this->isPendingReview() => 'En attente de validation',
            default => $this->publicationLabel(),
        };
    }

    // ---------- Accès ----------

    /**
     * Liaison de route : une œuvre non visible ne s'ouvre que pour son auteur ou un admin (sinon 404).
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $model = static::withUnpublished()->where($field ?? $this->getRouteKeyName(), $value)->first();

        if (! $model) {
            return null;
        }

        $user = auth()->user();

        if (! $model->isVisible() && ! ($model->isOwnedBy($user) || $user?->isStaff())) {
            return null;
        }

        return $model;
    }

    abstract public function isOwnedBy(?User $user): bool;
}
