<?php

namespace App\Listeners;

use App\Models\UserLoginHistory;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class CheckNewDeviceLogin
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
        $user = $event->user;
        $userId = $user->id;

        if (session("otp_verified_{$userId}") || session('otp_verifying')) {
            return;
        }

        $agent = new Agent();

        $deviceName = $agent->device() ?? 'Unknown';
        $browser    = $agent->browser() ?? 'Unknown';
        $ip         = request()->ip();

        // Check if this device has been seen before
        $known = UserLoginHistory::where('user_id', $userId)
            ->where('device_name', $deviceName)
            ->where('browser', $browser)
            ->exists();

        if ($known) return;

        // Generate OTP
        $otp = (string) random_int(100000, 999999);

        Cache::put("otp:{$userId}", $otp, now()->addMinutes(5)); // Expires in 5 minutes

        // Redirect user to OTP selection page
        session([
            'pending_otp' => true,
            'otp_user_id' => $userId,
            'otp_email'   => $user->email,
            'otp_phone'   => $user->phone_number,
            'otp_device_name' => $deviceName,
            'otp_browser' => $browser,
            'otp_ip' => $ip,
        ]);
    }
}
