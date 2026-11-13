<?php

namespace App\Notifications;

use App\Models\JoinRequest;
use App\Notifications\Concerns\CollectifMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Réponse au candidat : refus (avec message facultatif de l'équipe), ou acceptation quand
 * un compte existait déjà avec cette adresse (pas de nouveau compte, pas de mot de passe).
 */
class JoinRequestAnswered extends Notification
{
    use CollectifMail;

    public function __construct(
        public JoinRequest $joinRequest,
        public bool $accepted,
        public ?string $note = null,
        public bool $hasPassword = true,
        public bool $hasGoogle = false,
    )
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->collectifName();
        $greeting = 'Bonjour '.$this->joinRequest->name.',';

        if ($this->accepted) {
            return $this->collectifMail('Ta demande pour rejoindre '.$name.' est acceptée', [
                'greeting' => $greeting,
                'lines' => [
                    'Bonne nouvelle : ta demande pour rejoindre '.$name.' est acceptée.',
                    $this->hasPassword
                        ? 'Tu as déjà un compte avec cette adresse e-mail : connecte-toi avec ton mot de passe habituel'.($this->hasGoogle ? ' ou avec Google' : '').' pour publier tes œuvres.'
                        : 'Ton compte est maintenant un compte créateur : connecte-toi comme d’habitude avec « Continuer avec Google » pour publier tes œuvres.',
                ],
                'action' => ['label' => 'Me connecter', 'url' => route('login')],
                'after' => [$this->hasPassword
                    ? 'Mot de passe oublié ? Utilise le lien « Mot de passe oublié » sur la page de connexion.'
                    : 'Tu peux aussi définir un mot de passe depuis « Mon compte ».'],
            ]);
        }

        return $this->collectifMail('Ta demande pour rejoindre '.$name, [
            'greeting' => $greeting,
            'lines' => array_values(array_filter([
                'Merci de l’intérêt que tu portes à '.$name.'. Après avoir regardé ton travail, l’équipe ne peut pas donner suite à ta demande pour le moment.',
                $this->note ? 'Message de l’équipe :'."\n".$this->note : null,
                'Continue à créer : tu pourras refaire une demande plus tard.',
            ])),
        ]);
    }
}
