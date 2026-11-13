<?php

namespace App\Notifications;

use App\Models\JoinRequest;
use App\Notifications\Concerns\CollectifMail;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Nouvelle demande d'adhésion : cloche de l'en-tête pour l'équipe (comptes), e-mail pour
 * l'adresse adhesion.email_notification (envoi « à la volée »).
 */
class NewJoinRequest extends Notification
{
    use CollectifMail;

    public function __construct(public JoinRequest $joinRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['database'] : ['mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'adhesion',
            'message' => $this->joinRequest->name.' souhaite rejoindre le collectif ('.mb_strtolower($this->joinRequest->disciplineLabel()).').',
            'url' => $this->adminUrl(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->joinRequest;

        return $this->collectifMail('Nouvelle demande pour rejoindre '.$this->collectifName(), [
            'greeting' => 'Nouvelle demande d’adhésion',
            'lines' => array_values(array_filter([
                $request->name.' ('.$request->email.') souhaite rejoindre le collectif.',
                'Discipline : '.$request->disciplineLabel(),
                'Travail : '.$request->portfolio_url,
                $request->message ? 'Message : '.$request->message : null,
            ])),
            'action' => ['label' => 'Voir la demande', 'url' => $this->adminUrl()],
        ])->replyTo($request->email, $request->name);
    }

    private function adminUrl(): string
    {
        return \Illuminate\Support\Facades\Route::has('admin.join-requests.show')
            ? route('admin.join-requests.show', $this->joinRequest)
            : route('admin.index');
    }
}
