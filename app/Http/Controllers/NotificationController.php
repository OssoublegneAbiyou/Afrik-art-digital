<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Liste des notifications ; celles qui étaient non lues restent signalées sur cette page, puis sont marquées lues.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate(30);
        $unreadIds = $notifications->getCollection()->whereNull('read_at')->pluck('id')->all();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications', compact('notifications', 'unreadIds'));
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return redirect()->route('notifications.index')->with('success', 'Notifications effacées.');
    }
}
