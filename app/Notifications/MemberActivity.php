<?php

namespace App\Notifications;

use App\Models\Artist;
use App\Models\Illustration;
use App\Models\User;
use App\Models\Writer;
use Illuminate\Notifications\Notification;

/**
 * Notifications des membres, affichées dans le site (cloche de l'en-tête et page /notifications).
 * Une seule classe, plusieurs « kind » : nouvel abonné, œuvre mise en favori, profil du jour.
 */
class MemberActivity extends Notification
{
    public const NEW_FOLLOWER = 'nouvel-abonne';
    public const FAVORITED = 'favori';
    public const FEATURED = 'du-jour';
    public const TO_REVIEW = 'a-valider';
    public const REPORTED = 'signalement';
    public const APPROVED = 'validee';
    public const REJECTED = 'refusee';

    /**
     * Pour les admins : de nouvelles œuvres attendent une validation.
     */
    public static function toReview(User $author, int $count, string $what): self
    {
        return new self([
            'kind' => self::TO_REVIEW,
            'message' => $author->name.' a soumis '.\App\Support\Fr::count($count, $what).' à valider.',
            'url' => route('admin.moderation.index'),
        ]);
    }

    /**
     * Pour les admins : une œuvre a été signalée.
     */
    public static function reported(\Illuminate\Database\Eloquent\Model $work, string $reason): self
    {
        return new self([
            'kind' => self::REPORTED,
            'message' => '« '.$work->title.' » a été signalée : '.mb_strtolower($reason).'.',
            'url' => route('admin.reports.index'),
        ]);
    }

    /**
     * Pour l'auteur : son œuvre a été validée, refusée ou retirée.
     */
    public static function moderated(Illustration|\App\Models\Document $work, bool $approved, ?string $note = null): self
    {
        $isIllustration = $work instanceof Illustration;

        return new self([
            'kind' => $approved ? self::APPROVED : self::REJECTED,
            'message' => $approved
                ? '« '.$work->title.' » a été validée par l’équipe'.($work->isPublished() ? ' et est en ligne.' : '.')
                : '« '.$work->title.' » n’a pas été validée par l’équipe'.($note ? ' : '.$note : '.').' Tu peux la modifier et la soumettre à nouveau.',
            'url' => $approved
                ? ($isIllustration ? route('public.illustration', $work) : route('documents.read', $work))
                : ($isIllustration ? route('illustrations.edit', $work) : route('documents.edit', $work)),
            'thumbnail' => $isIllustration ? $work->thumbnailUrl() : $work->coverThumbnailUrl(),
        ]);
    }

    public function __construct(private array $payload)
    {
    }

    public static function newFollower(User $follower, Artist $artist): self
    {
        return new self([
            'kind' => self::NEW_FOLLOWER,
            'message' => $follower->name.' suit désormais ton studio.',
            'url' => route('public.artist', $artist),
        ]);
    }

    // ---------- Récits dessinés ----------

    public const COMIC_NEW_FOLLOWER = 'recit-abonne';
    public const COMIC_NEW_CHAPTER = 'recit-chapitre';
    public const COMIC_SCHEDULE_PENDING = 'recit-programme-en-attente';

    /** URL publique d'un récit (fiche), ou son espace de gestion si la fiche publique n'existe pas encore. */
    private static function comicUrl(\App\Models\Comic $comic): string
    {
        return \Illuminate\Support\Facades\Route::has('comics.show')
            ? route('comics.show', $comic)
            : route('comics.studio.manage', $comic);
    }

    /**
     * Pour l'auteur : quelqu'un suit désormais sa série (appelée par le contrôleur public « suivre »).
     */
    public static function newComicFollower(\App\Models\Comic $comic, User $follower): self
    {
        return new self([
            'kind' => self::COMIC_NEW_FOLLOWER,
            'message' => $follower->name.' suit désormais « '.$comic->titre.' ».',
            'url' => self::comicUrl($comic),
            'thumbnail' => $comic->coverUrl(),
        ]);
    }

    /**
     * Pour les abonnés : un nouveau chapitre est sorti.
     */
    public static function newComicChapter(\App\Models\ComicChapter $chapter): self
    {
        $comic = $chapter->comic;
        $url = \Illuminate\Support\Facades\Route::has('comics.read')
            ? route('comics.read', [$comic, $chapter->routeNumber()])
            : self::comicUrl($comic);

        return new self([
            'kind' => self::COMIC_NEW_CHAPTER,
            'message' => 'Nouveau chapitre : '.$comic->titre.' ch. '.$chapter->numberLabel()
                .($chapter->titre ? ' — '.$chapter->titre : '').'.',
            'url' => $url,
            'thumbnail' => $comic->coverUrl(),
        ]);
    }

    /**
     * Pour l'auteur : un chapitre (ou un one-shot) a été validé ou refusé par l'équipe.
     */
    public static function comicModerated(\App\Models\ComicChapter $chapter, bool $approved, ?string $note = null): self
    {
        $comic = $chapter->comic;
        $what = $comic->isOneShot() ? '« '.$comic->titre.' »' : '« '.$comic->titre.' » ch. '.$chapter->numberLabel();

        return new self([
            'kind' => $approved ? self::APPROVED : self::REJECTED,
            'message' => $approved
                ? $what.' a été validé par l’équipe'.($chapter->isPublished() ? ' et est en ligne.' : ' : il sortira à la date prévue.')
                : $what.' n’a pas été validé par l’équipe'.($note ? ' : '.$note : '.').' Tu peux le modifier et le soumettre à nouveau.',
            'url' => $approved && $chapter->isPublished() ? self::comicUrl($comic) : route('comics.studio.manage', $comic),
            'thumbnail' => $comic->coverUrl(),
        ]);
    }

    /**
     * Pour l'auteur : son récit entier a été masqué (signalement) ou remis en ligne par l'équipe.
     */
    public static function comicSeriesModerated(\App\Models\Comic $comic, bool $approved, ?string $note = null): self
    {
        return new self([
            'kind' => $approved ? self::APPROVED : self::REJECTED,
            'message' => $approved
                ? '« '.$comic->titre.' » a été validé par l’équipe.'
                : '« '.$comic->titre.' » a été masqué par l’équipe'.($note ? ' : '.$note : '.').' Tu peux le modifier ; contacte l’équipe pour le remettre en ligne.',
            'url' => route('comics.studio.manage', $comic),
            'thumbnail' => $comic->coverUrl(),
        ]);
    }

    // ---------- Animations ----------

    /**
     * Pour l'auteur : son animation a été validée, refusée ou masquée (signalement) par l'équipe.
     */
    public static function animationModerated(\App\Models\Animation $animation, bool $approved, ?string $note = null): self
    {
        $manage = \Illuminate\Support\Facades\Route::has('animations.studio.manage')
            ? route('animations.studio.manage', $animation)
            : route('dashboard');

        return new self([
            'kind' => $approved ? self::APPROVED : self::REJECTED,
            'message' => $approved
                ? '« '.$animation->titre.' » a été validée par l’équipe'.($animation->isPublished() ? ' et est en ligne.' : ' : elle sortira à la date prévue.')
                : '« '.$animation->titre.' » n’a pas été validée par l’équipe'.($note ? ' : '.$note : '.').' Tu peux la modifier et la soumettre à nouveau.',
            'url' => $approved && $animation->isVisible() && \Illuminate\Support\Facades\Route::has('animations.show')
                ? route('animations.show', $animation)
                : $manage,
            'thumbnail' => $animation->posterUrl(),
        ]);
    }

    /**
     * Pour l'auteur : l'heure de sortie programmée est passée mais l'équipe n'a pas encore validé.
     */
    public static function comicScheduledAwaitingReview(\App\Models\ComicChapter $chapter): self
    {
        $comic = $chapter->comic;
        $what = $comic->isOneShot() ? '« '.$comic->titre.' »' : '« '.$comic->titre.' » ch. '.$chapter->numberLabel();

        return new self([
            'kind' => self::COMIC_SCHEDULE_PENDING,
            'message' => $what.' devait sortir maintenant, mais attend encore la validation de l’équipe. Il sera publié dès qu’il sera validé.',
            'url' => route('comics.studio.manage', $comic),
            'thumbnail' => $comic->coverUrl(),
        ]);
    }

    public const JOIN_ACCEPTED = 'adhesion-acceptee';

    /**
     * Pour le membre : sa demande d'adhésion est acceptée (compte visiteur devenu illustrateur ou écrivain).
     */
    public static function joinAccepted(User $user, bool $upgraded): self
    {
        return new self([
            'kind' => self::JOIN_ACCEPTED,
            'message' => $upgraded
                ? 'Ta demande pour rejoindre le collectif est acceptée : ton compte est maintenant un compte '
                    .($user->isWriter() ? 'écrivain' : 'illustrateur').'. Ton espace de création t’attend.'
                : 'Ta demande pour rejoindre le collectif est acceptée.',
            'url' => route('dashboard'),
        ]);
    }

    public static function favorited(User $fan, Illustration $illustration): self
    {
        return new self([
            'kind' => self::FAVORITED,
            'message' => $fan->name.' a ajouté « '.$illustration->title.' » à ses favoris.',
            'url' => route('public.illustration', $illustration),
            'thumbnail' => $illustration->thumbnailUrl(),
        ]);
    }

    public static function featured(Artist|Writer $profile): self
    {
        $isArtist = $profile instanceof Artist;

        return new self([
            'kind' => self::FEATURED,
            'message' => $isArtist
                ? 'Ton studio est « Artiste du jour » : il est mis en avant sur l’accueil aujourd’hui.'
                : 'Tu es la « Plume du jour » : ton univers est mis en avant sur l’accueil aujourd’hui.',
            'url' => $isArtist ? route('public.artists') : route('public.writers'),
        ]);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
