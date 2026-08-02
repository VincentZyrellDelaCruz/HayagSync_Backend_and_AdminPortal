<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\OTPMail;
use App\Mail\SendOtpMail;
use App\Models\ActivityLog;
use App\Models\OtpVerification;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Twilio\Rest\Client;

class OtpController extends Controller
{
    public function index()
    {
        return view('auth.otp.otp-selection');
    }

    public function send(Request $request)
    {
        $userId = session('otp_user_id');
        $otp = Cache::get("otp:$userId");

        if ($request->method === 'email') {
            Mail::to(session('otp_email'))->send(new SendOtpMail($otp));
        } elseif ($request->method === 'phone') {
            $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
            $twilio->messages->create(session('otp_phone'), [
                'from' => config('services.twilio.from'),
                'body' => "Your OTP code is $otp"
            ]);
        }

        return redirect()->route('otp.verify');
    }

    public function verifyForm()
    {
        return view('auth.otp.otp-verify');
    }

    public function verify(Request $request)
    {
        $userId = session('otp_user_id');
        $otp = Cache::get("otp:$userId");

        $attemptKey = "otp_attempts:$userId";
        $attempts = Cache::get($attemptKey, 0);

        if ($attempts >= 5) {
            // Lock account temporarily
            SecurityEvent::create([
                'user_id'    => $userId,
                'severity'   => 'high',
                'event_type' => 'otp_lockout',
                'description'=> 'User exceeded maximum OTP attempts',
                'ip_address' => $request->ip(),
                'location'   => null,
                'status'     => 'locked',
            ]);

            return back()->withErrors([
                'otp_code' => 'Too many failed attempts. Please try again in 15 minutes.'
            ]);
        }

        if ($otp && $otp == $request->otp_code) {
            Cache::forget("otp:$userId");
            Cache::forget($attemptKey);
            Auth::loginUsingId($userId);

            ActivityLog::create([
                'user_id'    => $userId,
                'action_type'=> 'otp_success',
                'description'=> 'User successfully verified OTP',
                'module'     => 'auth',
                'ip_address' => $request->ip(),
            ]);

            UserLoginHistory::create([
                'user_id' => $userId,
                'device_name' => request()->header('User-Agent'),
                'browser' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'login_time' => now(),
            ]);

            return redirect()->intended('/dashboard');
        }

        Cache::put($attemptKey, $attempts + 1, now()->addMinutes(15));

        ActivityLog::create([
            'user_id'    => $userId,
            'action_type'=> 'otp_failed',
            'description'=> 'User failed OTP verification',
            'module'     => 'auth',
            'ip_address' => $request->ip(),
        ]);

        return back()->withErrors(['otp_code' => 'Invalid or expired OTP']);
    }
}
