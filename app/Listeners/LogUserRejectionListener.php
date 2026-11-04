<?php

namespace App\Listeners;

use App\Events\UserRejected;
use Illuminate\Support\Facades\Log;

class LogUserRejectionListener
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
    public function handle(UserRejected $event): void
    {
        Log::info('Utilisateur refusé', [
            'action' => 'refus',
            'user_id' => $event->user->id,
            'date' => now()->toIso8601String(),
        ]);
    }
}
