<?php

namespace App\Listeners;

use App\Events\UserLoginFailed;
use Illuminate\Support\Facades\Log;

class LogUserLoginFailureListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserLoginFailed $event): void
    {
        Log::info('Connexion échouée', [
            'action' => 'connexion_echouee',
            'date' => now()->toIso8601String(),
            'ip' => $event->ipAddress,
        ]);
    }
}
