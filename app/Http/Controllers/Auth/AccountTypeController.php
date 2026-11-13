<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountTypeController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if ($request->user()->account_type_selected) {
            return redirect()->route('dashboard');
        }

        return view('auth.account-type');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Le type ne se choisit qu'une fois : ensuite, seule l'équipe le change (admin ou demande d'adhésion).
        if ($user->account_type_selected) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate([
            'account_type' => ['required', 'in:artist,writer,visitor'],
        ]);

        // Un compte venu de Google reste visiteur : les comptes créateurs sont attribués par l'équipe.
        if ($user->google_id !== null) {
            $data['account_type'] = 'visitor';
        }

        $user->update([
            'account_type' => $data['account_type'],
            'account_type_selected' => true,
        ]);

        if ($user->isWriter()) {
            $user->writer()->firstOrCreate([]);
        } elseif ($user->isArtist()) {
            $user->artist()->firstOrCreate([]);
        }

        return redirect()->route('dashboard');
    }
}
