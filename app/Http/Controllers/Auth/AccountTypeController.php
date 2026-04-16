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
        $data = $request->validate([
            'account_type' => ['required', 'in:artist,writer,visitor'],
        ]);

        $user = $request->user();
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
