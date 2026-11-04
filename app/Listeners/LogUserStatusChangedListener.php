<?php

namespace App\Listeners;

use App\Events\UserStatusChanged;
use Illuminate\Support\Facades\Log;

class LogUserStatusChangedListener
{
    public function handle(UserStatusChanged $event): void
    {
        Log::info('Statut utilisateur modifié', [
            'action' => $event->action,
            'user_id' => $event->user->id,
            'status' => $event->user->status,
            'date' => now()->toIso8601String(),
        ]);
    }
}
