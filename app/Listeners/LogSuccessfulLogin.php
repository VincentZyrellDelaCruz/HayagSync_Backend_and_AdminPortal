<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\UserLoginHistory;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Jenssegers\Agent\Agent;

class LogSuccessfulLogin
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
    public function handle(Login $event): void
    {
        $agent = new Agent();

        ActivityLog::create([
            'user_id'    => $event->user->id,
            'action_type'=> 'login_success',
            'description'=> 'User logged in successfully',
            'module'     => 'auth',
            'ip_address' => request()->ip(),
        ]);

        UserLoginHistory::create([
            'user_id'    => $event->user->id ?? null,
            'device_name' => $agent->device(),
            'browser'     => $agent->browser(),
            'ip_address'  => request()->ip(),
            'location'    => null, // optional: use IP geolocation service
            'login_time'  => now(),
        ]);
    }
}
