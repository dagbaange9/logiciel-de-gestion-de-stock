<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use Illuminate\Support\Facades\Log;

class LogUserLoginListener
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
    public function handle(UserLoggedIn $event): void
    {
        Log::info('Connexion réussie', [
            'action' => 'connexion_reussie',
            'user_id' => $event->user->id,
            'ip' => $event->ipAddress,
            'date' => now()->toIso8601String(),
        ]);
    }
}
