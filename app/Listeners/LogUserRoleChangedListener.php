<?php

namespace App\Listeners;

use App\Events\UserRoleChanged;
use Illuminate\Support\Facades\Log;

class LogUserRoleChangedListener
{
    public function handle(UserRoleChanged $event): void
    {
        Log::info('Rôle utilisateur modifié', [
            'action' => 'changement_role',
            'user_id' => $event->user->id,
            'previous_role_id' => $event->previousRoleId,
            'role_id' => $event->user->role_id,
            'date' => now()->toIso8601String(),
        ]);
    }
}
