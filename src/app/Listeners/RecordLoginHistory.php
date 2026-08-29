<?php

namespace App\Listeners;

use App\Models\SecurityEvent;
use App\Models\UserLoginHistory;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Jenssegers\Agent\Agent;

class RecordLoginHistory
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
        $request = request();

        if (!$request->hasSession()) return;

        $user = $event->user;

        $agent = new Agent();

        $userAgent = $request->userAgent() ?? '';

        $agent->setUserAgent($userAgent);

        $device = $agent->device();
        $browser = $agent->browser();

        if (!$device) $device = 'Unknown Device';

        if (!$browser) $browser = 'Unknown Browser';

        $ipAddress = $request->ip();

        $location = null; // Optional: use IP geolocation service

        $prevLogin = UserLoginHistory::where('user_id', $user->id)
            ->latest('login_time')
            ->first();

        $login = UserLoginHistory::create([
            'user_id'    => $user->id,
            'device_name' => $device,
            'browser'     => $browser,
            'ip_address'  => $ipAddress,
            'location'    => $location,
            'login_time'  => now(),
        ]);

        if (!$prevLogin) return;

        $sameDevice = $prevLogin->device_name === $device;
        $sameBrowser = $prevLogin->browser === $browser;
        $sameIp = $prevLogin->ip_address === $ipAddress;

        if (!$sameDevice || !$sameBrowser || !$sameIp) {
            $alreadyFlagged = SecurityEvent::where('user_id', $user->id)
                ->where('event_type', 'New Device or Location Login')
                ->where('status', '!=', 'resolved')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->exists();

            if (!$alreadyFlagged) {
                SecurityEvent::create([
                    'user_id' => $user->id,
                    'severity' => 'medium',
                    'event_type' => 'New Device or Location Login',
                    'description' => sprintf(
                        'Successful login detected from a device, browser, or IP address that differs from the user’s recent login history. Device: %s; Browser: %s; IP: %s.',
                        $device,
                        $browser,
                        $ipAddress ?? 'Unknown'
                    ),
                    'ip_address' => $ipAddress,
                    'location' => $location,
                    'status' => 'open',
                ]);
            }
        }
    }
}
