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
        $agent = new Agent();

        $deviceName = $agent->device() ?? 'Unknown';
        $browser    = $agent->browser() ?? 'Unknown';
        $ip         = request()->ip();

        // Check if this device has been seen before
        $known = UserLoginHistory::where('user_id', $user->id)
            ->where('device_name', $deviceName)
            ->where('ip_address', $ip)
            ->exists();

        if (!$known) {
            // Generate OTP
            $otp = rand(100000, 999999);
            $key = "otp:{$user->id}";
            Cache::put($key, $otp, now()->addMinutes(5)); // expires in 5 minutes

            // Redirect user to OTP selection page
            session([
                'pending_otp' => true,
                'otp_user_id' => $user->id,
                'otp_email'   => $user->email,
                'otp_phone'   => $user->phone_number,
            ]);

            session()->save();

            Auth::logout();

            // dd(session('otp_email'));
            // redirect()->route('otp.select')->send();
        } else {
            // Record login history if device is known
            UserLoginHistory::create([
                'user_id'    => $user->id,
                'device_name'=> $deviceName,
                'browser'    => $browser,
                'ip_address' => $ip,
                'location'   => null,
                'login_time' => now(),
            ]);
        }
    }
}
