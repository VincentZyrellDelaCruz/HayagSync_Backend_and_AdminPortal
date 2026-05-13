<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $req): JsonResponse
    {
        $validated = $req->validate([
            'email' => 'required|email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'gender' => 'required|string',
            'birthdate' => 'required|date',
            'occupation' => 'required|string',
            'phone_number' => 'required',
            'student_id' => 'required|string',
            'relationship' => 'required|string',

            // Proofs must be an array of exactly 3 files
            'proofs' => 'required|array|size:3',
            'proofs.*' => 'file|mimes:jpg,jpeg,png|max:5120', // 5MB per file
        ]);

        DB::beginTransaction();

        try {
            // Create pending registration
            $pending = PendingRegistration::create([
                'email' => $validated['email'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'suffix' => $validated['suffix'] ?? null,
                'gender' => $validated['gender'],
                'birthdate' => $validated['birthdate'],
                'occupation' => $validated['occupation'],
                'phone_number' => $validated['phone_number'],
                'student_id' => $validated['student_id'],
                'relationship' => $validated['relationship'],
                'status' => 'Pending',
            ]);

            // Save proofs
            foreach ($req->file('proofs') as $index => $file) {
                $path = $file->store('register_proofs', 'public');
                $mime_type = $file->getMimeType();

                $pending->proofs()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $mime_type,
                    'proof_type' => match ($index) {
                        0 => 'Selfie',
                        1 => 'Valid ID',
                        2 => 'Student ID',
                        default => 'Other',
                    },
                    'hash_signature' => hash_file('sha256', $file->getRealPath()),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pending registration created successfully.',
                'data' => $pending->load('proofs'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create pending registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
        $user = User::with('parent_guardian.students')
            ->select('id', 'first_name', 'last_name', 'middle_name', 'suffix', 'email')
            ->find($req->user()->id);

        $report_count = $user->incidents()->count();
        $related_child_count = $user->parent_guardian?->students->count();

        return response()->json([
            'user' => $user,
            'report_count' => $report_count,
            'related_child_count' => $related_child_count,
        ], 200);
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
