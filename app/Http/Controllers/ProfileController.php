<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\MemberAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     * Avec mot de passe : il est demandé. Compte créé avec Google (sans mot de passe) : l'adresse e-mail
     * du compte doit être recopiée.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasPassword()) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        } else {
            $request->validateWithBag('userDeletion', [
                'confirm_email' => ['required', 'string'],
            ], ['confirm_email.required' => 'Recopie ton adresse e-mail pour confirmer.']);

            if (Str::lower(trim((string) $request->input('confirm_email'))) !== Str::lower($user->email)) {
                throw ValidationException::withMessages(['confirm_email' => 'Cette adresse ne correspond pas à celle de ton compte.'])
                    ->errorBag('userDeletion');
            }
        }

        // Le dernier administrateur actif ne peut pas disparaître.
        if (MemberAccounts::isLastActiveAdmin($user)) {
            throw ValidationException::withMessages([$user->hasPassword() ? 'password' : 'confirm_email' => 'Tu es le dernier administrateur actif : nomme d’abord un autre administrateur.'])
                ->errorBag('userDeletion');
        }

        Auth::logout();

        MemberAccounts::deleteFiles($user);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
