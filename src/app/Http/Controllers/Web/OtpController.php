<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\ActivityLog;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Twilio\Rest\Client;

class OtpController extends Controller
{
    public function index()
    {
        abort_unless(session('pending_otp'), 403);

        return Inertia::render('auth/otp/otp-selection', [
            'email' => $this->maskEmail(session('otp_email')),
            'phone' => $this->maskPhone(session('otp_phone')),
            'hasPhone' => filled(session('otp_phone')),
        ]);
    }

    public function send(Request $request)
    {
        abort_unless(session('pending_otp'), 403);

        $request->validate([
            'method' => ['required', 'in:email,phone'],
        ]);

        $userId = session('otp_user_id');
        $method = $request->input('method');
        $user = User::findOrFail($userId);

        $rateLimitKey = "otp_send:{$userId}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            throw ValidationException::withMessages([
                'method' => "Please wait {$seconds} seconds before requesting another code.",
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        Cache::put("otp:{$userId}", $otp, now()->addMinutes(5)); // The OTP will xpire in 5 minutes

        session([
            'otp_method' => $method,
            'otp_sent_at' => now()->timestamp,
        ]);

        RateLimiter::hit($rateLimitKey, 60);

        // EMAIL
        if ($method === 'email') {
            Mail::to($user->email)->queue(new SendOtpMail($otp));
        }
        else {
            // PHONE NUMBER THROUGH TWILIO (CURRENTLY UNAVAILABLE)
            if (!$user->phone_number) {
                throw ValidationException::withMessages([
                    'method' => 'No phone number is registered for this account.',
                ]);
            }

            $twilio = new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilio->messages->create($user->phone_number, [
                'from' => config('services.twilio.from'),
                'body' => "Your HayagSync verification code is {$otp}. It expires in 5 minutes.",
            ]);
        }

        ActivityLog::create([
            'user_id' => $userId,
            'action_type' => 'otp_sent',
            'description' => "OTP sent through {$method}",
            'module' => 'auth',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('otp.verify');
    }

    public function verifyForm()
    {
        abort_unless(session('pending_otp'), 403);

        return Inertia::render('auth/otp/otp-verify', [
            'method' => session('otp_method'),
            'destination' => session('otp_method') === 'phone'
                ? $this->maskPhone(session('otp_phone'))
                : $this->maskEmail(session('otp_email')),
        ]);
    }

    public function verify(Request $request)
    {
        abort_unless(session('pending_otp'), 403);

        $request->validate([
            'otp_code' => ['required', 'digits:6'],
        ]);

        $userId = session('otp_user_id');
        $otpKey = "otp:{$userId}";
        $attemptKey = "otp_attempts:{$userId}";

        $attempts = Cache::get($attemptKey, 0);

        if ($attempts >= 5) {
            SecurityEvent::create([
                'user_id' => $userId,
                'severity' => 'high',
                'event_type' => 'otp_lockout',
                'description' => 'User exceeded maximum OTP attempts',
                'ip_address' => $request->ip(),
                'location' => null,
                'status' => 'locked',
            ]);

            throw ValidationException::withMessages([
                'otp_code' => 'Too many failed attempts. Please try again in 15 minutes.',
            ]);
        }

        $storedOtp = Cache::get($otpKey);

        if (!$storedOtp) {
            throw ValidationException::withMessages([
                'otp_code' => 'This verification code has expired. Please request a new one.',
            ]);
        }

        if (!hash_equals((string) $storedOtp, (string) $request->otp_code)) {
            Cache::put(
                $attemptKey,
                $attempts + 1,
                now()->addMinutes(15)
            );

            ActivityLog::create([
                'user_id' => $userId,
                'action_type' => 'otp_failed',
                'description' => 'User failed OTP verification',
                'module' => 'auth',
                'ip_address' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'otp_code' => 'Invalid verification code.',
            ]);
        }

        $deviceName = session('otp_device_name');
        $browser = session('otp_browser');

        Cache::forget($otpKey);
        Cache::forget($attemptKey);

        session()->put('otp_verifying', true);

        Auth::loginUsingId($userId);

        $request->session()->regenerate();

        session()->forget('otp_verifying');

        session()->put("otp_verified_{$userId}", true);

        ActivityLog::create([
            'user_id' => $userId,
            'action_type' => 'otp_success',
            'description' => 'User verified OTP successfully',
            'module' => 'auth',
            'ip_address' => $request->ip(),
        ]);

        UserLoginHistory::create([
            'user_id' => $userId,
            'device_name' => $deviceName ?? 'Unknown',
            'browser' => $browser ?? 'Unknown',
            'ip_address' => $request->ip(),
            'location' => null,
            'login_time' => now(),
        ]);

        session()->forget([
            'pending_otp',
            'otp_user_id',
            'otp_email',
            'otp_phone',
            'otp_method',
            'otp_sent_at',
            'otp_device_name',
            'otp_browser',
            'otp_ip',
        ]);

        return redirect()->intended(route('dashboard'));
    }

    private function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return null;
        }

        [$name, $domain] = explode('@', $email, 2);

        $visible = substr($name, 0, min(2, strlen($name)));

        return $visible . str_repeat('*', max(strlen($name) - strlen($visible), 2)) . '@' . $domain;
    }

    private function maskPhone(?string $phone): ?string
    {
        if (!$phone) return null;

        $length = strlen($phone);

        return str_repeat('*', max($length - 4, 4)) . substr($phone, -4);
    }
}
