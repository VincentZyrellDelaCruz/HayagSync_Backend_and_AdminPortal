<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogFailedLogin
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
    public function handle(Failed $event): void
    {
        ActivityLog::create([
            'user_id'    => optional($event->user)->id, // may be null if user not found
            'action_type'=> 'login_failed',
            'description'=> 'Login attempt failed for email: '.$event->credentials['email'],
            'module'     => 'auth',
            'ip_address' => request()->ip(),
        ]);
    }
}
