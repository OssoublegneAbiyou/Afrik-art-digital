<?php

namespace App\Http\Controllers;

use App\Models\JoinRequest;
use App\Notifications\NewJoinRequest;
use App\Support\AdminNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Formulaire public « Rejoindre le collectif » (page À propos). Le traitement des demandes se fait
 * dans l'admin : App\Http\Controllers\Admin\JoinRequestController.
 */
class JoinRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $back = route('public.about').'#rejoindre';
        $confirmation = (string) setting('adhesion.message_confirmation', 'Merci, ta demande est bien arrivée.');

        // Champ piège invisible : un robot le remplit, un humain non. On fait comme si tout allait bien.
        if (filled($request->input('site_web'))) {
            return redirect()->to($back)->with('join_success', $confirmation);
        }

        if (! setting('adhesion.ouverte', true)) {
            return redirect()->to($back)->withErrors([
                'adhesion' => (string) setting('adhesion.message_ferme', 'Les adhésions sont fermées pour le moment.'),
            ], 'join');
        }

        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validateWithBag('join', [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'discipline' => ['required', Rule::in(array_keys(JoinRequest::DISCIPLINES))],
            'portfolio_url' => ['required', 'url:http,https', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Indique ton nom ou ton nom d’artiste.',
            'email.required' => 'Indique une adresse e-mail pour qu’on puisse te répondre.',
            'email.email' => 'Cette adresse e-mail ne semble pas valide.',
            'discipline.required' => 'Choisis ta discipline.',
            'portfolio_url.required' => 'Ajoute un lien vers ton travail (Instagram, Behance, blog, Drive…).',
            'portfolio_url.url' => 'Le lien doit commencer par https:// (par exemple https://instagram.com/ton-compte).',
        ]);

        $joinRequest = JoinRequest::create($data);

        $this->notifyTeam($joinRequest);

        return redirect()->to($back)->with('join_success', $confirmation);
    }

    /**
     * Cloche de l'équipe (modérateurs et admins) + e-mail à adhesion.email_notification (à défaut, l'e-mail
     * du collectif). Un échec d'envoi ne doit jamais faire perdre la demande : elle reste dans l'admin.
     */
    private function notifyTeam(JoinRequest $joinRequest): void
    {
        AdminNotifier::send(new NewJoinRequest($joinRequest));

        $email = setting('adhesion.email_notification') ?: setting('contact.email');

        if (! $email) {
            return;
        }

        try {
            Notification::route('mail', $email)->notify(new NewJoinRequest($joinRequest));
        } catch (Throwable $exception) {
            Log::warning('Join request notification failed', ['id' => $joinRequest->id, 'error' => $exception->getMessage()]);
        }
    }
}
