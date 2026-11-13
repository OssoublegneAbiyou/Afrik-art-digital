<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as Notifier;
use Throwable;

/**
 * Prévient tous les admins (cloche de l'en-tête). Un échec ne doit jamais bloquer l'action du membre.
 */
class AdminNotifier
{
    public static function send(Notification $notification, ?User $except = null): void
    {
        try {
            $admins = User::whereIn('role', [User::ROLE_MODERATOR, User::ROLE_ADMIN])->whereNull('deactivated_at')
                ->when($except, fn ($query) => $query->whereKeyNot($except->id))
                ->get();

            if ($admins->isNotEmpty()) {
                Notifier::send($admins, $notification);
            }
        } catch (Throwable $exception) {
            Log::warning('Admin notification failed', ['error' => $exception->getMessage()]);
        }
    }
}
