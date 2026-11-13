<?php

namespace App\Notifications;

use App\Notifications\Concerns\CollectifMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Compte créé par l'équipe (demande acceptée ou création directe) : lien pour choisir son mot de passe.
 * Aucun mot de passe n'est jamais envoyé par e-mail.
 */
class AccountInvitation extends Notification
{
    use CollectifMail;

    public function __construct(public string $token, public bool $fromJoinRequest = false)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function url(object $notifiable): string
    {
        return route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->collectifName();
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return $this->collectifMail('Ton compte sur '.$name.' est prêt', [
            'greeting' => 'Bonjour '.$notifiable->name.',',
            'lines' => [
                $this->fromJoinRequest
                    ? 'Bonne nouvelle : ta demande pour rejoindre '.$name.' est acceptée. Ton compte est créé.'
                    : 'L’équipe de '.$name.' vient de te créer un compte.',
                'Pour te connecter, choisis d’abord ton mot de passe avec le bouton ci-dessous. Ton identifiant est cette adresse e-mail : '.$notifiable->email.'.',
            ],
            'action' => ['label' => 'Choisir mon mot de passe', 'url' => $this->url($notifiable)],
            'after' => [
                'Ce lien est valable '.$expire.' minutes. Passé ce délai, utilise « Mot de passe oublié » sur la page de connexion avec la même adresse.',
            ],
        ]);
    }
}
