<?php

namespace App\Listeners;

use App\Events\UserEmailChanged;
use Illuminate\Support\Facades\Log;

class LogUserEmailChangedListener
{
    public function handle(UserEmailChanged $event): void
    {
        Log::info('Adresse e-mail vérifiée et modifiée', [
            'action' => 'changement_email',
            'user_id' => $event->user->id,
            'date' => now()->toIso8601String(),
        ]);
    }
}
