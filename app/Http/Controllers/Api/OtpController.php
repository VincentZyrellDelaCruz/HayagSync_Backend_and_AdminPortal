<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OTPMail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function generateOTP(Request $req): JsonResponse
    {
        $validated = $req->validate([
            'email' => 'required|email',
        ]);

        $email = $validated['email'];

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'message' => 'Your email is already exist!',
            ], 409);
        }

        $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpVerification::updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'otp_code' => bcrypt($otp_code),
                'expires_at' => now()->addMinutes(10),
            ]
        );

        if($this->sendOTPToEmail($email, $otp_code)) {
            return response()->json([
                'message' => 'Your OTP Code is now sending to your Email Address.'
            ]);
        }

        return response()->json([
            'message' => 'Something went wrong. Please try again.'
        ], 500);
    }

    protected function sendOTPToEmail(String $email, String $otp_code)
    {
        Mail::to($email)
            ->send(new OTPMail($otp_code));

        return true;
    }

    public function validateOTP(Request $req): JsonResponse
    {
        $validated = $req->validate([
            'email'  => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        $email = $validated['email'];

        $otp_verification = OtpVerification::where('email', $email)->first();

        if (!$otp_verification) {
            return response()->json([
                'message' => 'No OTP request found for this email.'
            ], 404);
        }

        if ($otp_verification->expires_at->isPast()) {
            $otp_verification->delete();

            return response()->json([
                'message' => 'Your OTP Code has expired!'
            ], 410);
        }

        $otp_code = $validated['otp_code'];

        if (Hash::check($otp_code, $otp_verification->otp_code)) {
            $otp_verification->delete();

            return response()->json([
                'message' => 'Your OTP Code matched successfully!'
            ], 202);
        }

        return response()->json([
            'message' => 'Your OTP Code did not matched! Try again'
        ], 401);
    }
}
