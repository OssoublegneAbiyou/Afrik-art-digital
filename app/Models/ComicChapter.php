<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chapitre d'un récit dessiné. Brouillon (published_at null), programmé (date future) ou publié ;
 * validation par l'équipe via moderation_status (HasPublication). Un one-shot a un seul chapitre, numéro 1.
 */
class ComicChapter extends Model
{
    use HasPublication, SoftDeletes;

    protected $fillable = [
        'comic_id', 'numero', 'titre', 'note_auteur', 'pages_count', 'published_at', 'moderation_status',
        'moderation_note', 'moderated_at', 'rights_certified_at', 'notified_at', 'retard_signale_at',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'float',
            'notified_at' => 'datetime',
            'retard_signale_at' => 'datetime',
        ];
    }

    /**
     * Statut de validation d'un nouveau chapitre : l'équipe publie directement ; sinon le réglage
     * bd.validation_avant_publication (« suit » = règle générale, « oui » = toujours, « non » = jamais).
     */
    public static function moderationStatusFor(?User $author): string
    {
        if ($author?->isStaff()) {
            return self::MODERATION_APPROVED;
        }

        $required = match ((string) setting('bd.validation_avant_publication', 'suit')) {
            'oui' => true,
            'non' => false,
            default => PlatformSetting::moderationEnabled(),
        };

        return $required ? self::MODERATION_PENDING : self::MODERATION_APPROVED;
    }

    /** « title » générique (modération, signalements, journal) : « Série ch. 2 », ou le titre du one-shot. */
    public function getTitleAttribute(): string
    {
        $comic = $this->comic;

        if (! $comic) {
            return 'Chapitre '.$this->numberLabel();
        }

        return $comic->isOneShot() ? $comic->titre : $comic->titre.' ch. '.$this->numberLabel();
    }

    public function comic()
    {
        return $this->belongsTo(Comic::class)->withoutGlobalScope('published');
    }

    public function pages()
    {
        return $this->hasMany(ComicPage::class)->orderBy('ordre');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function readingProgress()
    {
        return $this->hasMany(ComicReadingProgress::class);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->comic?->user_id === $user->id;
    }

    public function authorUser(): ?User
    {
        return $this->comic?->user;
    }

    /** « 2 », « 2,5 » (chapitre bonus). */
    public function numberLabel(): string
    {
        $n = (float) $this->numero;

        return floor($n) == $n ? (string) (int) $n : str_replace('.', ',', rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.'));
    }

    /** Valeur d'URL du numéro : « 2 », « 2.5 ». */
    public function routeNumber(): string
    {
        $n = (float) $this->numero;

        return floor($n) == $n ? (string) (int) $n : number_format($n, 1, '.', '');
    }
}
