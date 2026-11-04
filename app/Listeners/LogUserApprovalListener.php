<?php

namespace App\Listeners;

use App\Events\UserApproved;
use Illuminate\Support\Facades\Log;

class LogUserApprovalListener
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
    public function handle(UserApproved $event): void
    {
        Log::info('Utilisateur approuvé', [
            'action' => 'approbation',
            'user_id' => $event->user->id,
            'role_id' => $event->user->role_id,
            'date' => now()->toIso8601String(),
        ]);
    }
}
