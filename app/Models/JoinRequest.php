<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Demande d'adhésion envoyée depuis la page À propos, traitée dans l'admin (Demandes d'adhésion).
 */
class JoinRequest extends Model
{
    public const DISCIPLINES = [
        'illustration' => 'Illustration',
        'ecriture' => 'Écriture',
        'les-deux' => 'Illustration et écriture',
    ];

    public const STATUS_NEW = 'nouvelle';
    public const STATUS_ACCEPTED = 'acceptee';
    public const STATUS_REFUSED = 'refusee';

    public const STATUSES = [
        self::STATUS_NEW => 'Nouvelle',
        self::STATUS_ACCEPTED => 'Acceptée',
        self::STATUS_REFUSED => 'Refusée',
    ];

    protected $fillable = [
        'name',
        'email',
        'discipline',
        'portfolio_url',
        'message',
        'handled_at',
        'statut',
        'traite_par',
        'traite_le',
        'note_interne',
        'motif_refus',
        'user_id',
    ];

    protected $attributes = [
        'statut' => self::STATUS_NEW,
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
            'traite_le' => 'datetime',
        ];
    }

    /**
     * Nombre de nouvelles demandes (pastille du menu d'administration, tableau de bord).
     */
    public static function pendingCount(): int
    {
        return static::query()->where('statut', self::STATUS_NEW)->count();
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    /** Compte créé (ou retrouvé) à l'acceptation. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function disciplineLabel(): string
    {
        return self::DISCIPLINES[$this->discipline] ?? $this->discipline;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->statut] ?? $this->statut;
    }

    public function isNew(): bool
    {
        return $this->statut === self::STATUS_NEW;
    }

    public function isHandled(): bool
    {
        return ! $this->isNew();
    }

    /**
     * Type de compte suggéré à l'acceptation (une seule « porte » possible : « les deux » ouvre un studio).
     */
    public function suggestedAccountType(): string
    {
        return $this->discipline === 'ecriture' ? 'writer' : 'artist';
    }
}
