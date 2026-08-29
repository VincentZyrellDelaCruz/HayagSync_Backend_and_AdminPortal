<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class ClearFailedLoginCounter
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
        $email = strtolower(trim((string) $event->user->email));

        $ipAddress = request()->ip();

        $accountKey = 'security:failed-login:account:' . hash('sha256', $email);

        $ipKey = 'security:failed-login:ip:' . hash('sha256', (string) $ipAddress);

        // DELETE STORED CACHES
        Cache::forget($accountKey);
        Cache::forget($ipKey);
    }
}
