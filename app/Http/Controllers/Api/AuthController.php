<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        // Require image and connect to existing student records
    }

    public function login(Request $req): JsonResponse
    {
        $req->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_token' => 'nullable|string',
        ]);

        if (!Auth::attempt($req->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials. Please try again.',
            ], 401);
        }

        $user = User::where('email', $req->input('email'))->first();
        $parent_guardian = ParentGuardian::where('user_id', $user->id)->first();

        if (!$parent_guardian) {
            return response()->json([
                'message' => 'This account is not allowed to use the mobile app.'
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('flutter-token')->plainTextToken;

        if ($req->filled('device_token')) {
            $user->device_tokens()->delete();

            DeviceToken::updateOrCreate([
                'user_id' => $user->id,
                'token' => $req->input('device_token'),
            ]);
        }

        return response()->json([
            'message' => 'Login Success!',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    public function profile(Request $req): JsonResponse
    {
        return response()->json($req->user(), 200);
    }

    public function logout(Request $req): JsonResponse
    {
        $req->user()->currentAccessToken()->delete();

        $req->user()->device_tokens()->delete();

        return response()->json([
            'message' => 'Logout Success!'
        ], 200);
    }
}
