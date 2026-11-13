<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Signalement d'une œuvre (illustration ou texte) par un visiteur ou un membre.
 */
class Report extends Model
{
    public const REASONS = [
        'droits' => 'Droits d’auteur (œuvre copiée, personnages ou logo protégés)',
        'contenu' => 'Contenu choquant ou inapproprié',
        'spam' => 'Publicité ou spam',
        'autre' => 'Autre raison',
    ];

    public const OPEN = 'open';
    public const DISMISSED = 'dismissed';
    public const ACTIONED = 'actioned';

    protected $fillable = ['user_id', 'reason', 'message', 'email', 'status', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    /**
     * Nombre d'œuvres signalées en attente de traitement (pastille du menu d'administration).
     */
    public static function openCount(): int
    {
        return static::query()->where('status', self::OPEN)
            ->select('reportable_type', 'reportable_id')->distinct()->get()->count();
    }

    /**
     * L'œuvre signalée, même si elle n'est plus visible publiquement (retirée, repassée en brouillon…).
     */
    public function reportable()
    {
        return $this->morphTo()->withoutGlobalScope('published');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
