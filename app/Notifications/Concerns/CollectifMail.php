<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * E-mails sobres en français, signés du nom du collectif (réglage identite.nom).
 */
trait CollectifMail
{
    protected function collectifName(): string
    {
        return (string) setting('identite.nom', config('site.name'));
    }

    /**
     * @param  array{greeting?: string, lines?: array<string>, action?: array{label: string, url: string}|null, after?: array<string>}  $content
     */
    protected function collectifMail(string $subject, array $content): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->view(['emails.collectif', 'emails.collectif-text'], $content + [
                'subjectLine' => $subject,
                'signature' => 'L’équipe de '.$this->collectifName(),
            ]);
    }
}
