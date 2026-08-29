<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogLogout
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
    public function handle(Logout $event): void
    {
        ActivityLog::create([
            'user_id'    => $event->user->id,
            'action_type'=> 'logout_success',
            'description'=> 'User logged out  successfully',
            'module'     => 'auth',
            'ip_address' => request()->ip(),
        ]);
    }

}
