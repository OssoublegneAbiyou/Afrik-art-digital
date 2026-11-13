<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     * Un compte créé avec Google n'a pas encore de mot de passe : l'ancien n'est alors pas demandé.
     */
    public function update(Request $request): RedirectResponse
    {
        $hasPassword = $request->user()->password !== null;

        $validated = $request->validateWithBag('updatePassword', array_filter([
            'current_password' => $hasPassword ? ['required', 'current_password'] : null,
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]));

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', $hasPassword ? 'password-updated' : 'password-set');
    }

    /**
     * Envoie un lien « Définir un mot de passe » (broker de réinitialisation) à l'adresse du compte.
     */
    public function sendSetLink(Request $request): RedirectResponse
    {
        $status = PasswordBroker::sendResetLink(['email' => $request->user()->email]);

        return back()->with('status', $status === PasswordBroker::RESET_LINK_SENT ? 'password-link-sent' : 'password-link-failed');
    }
}
