<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function registerParent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users',
            'password'               => 'required|string|min:6',
            'certificate_filename'   => 'required|string',
            'certificate_content_mock' => 'nullable|string',
            'student_name'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        // Simulated OCR verification logic (kept from old controller)
        $textToScan = strtoupper($request->input('certificate_content_mock') ?? $request->input('certificate_filename'));

        if (strpos($textToScan, 'ENROLLED') === false) {
            return response()->json(['message' => "Verification Failed: Document missing 'ENROLLED'."], 422);
        }
        if (strpos($textToScan, '2026') === false) {
            return response()->json(['message' => "Verification Failed: Academic Year must be 2026-2027."], 422);
        }

        // Extract student details
        $studentName    = $request->student_name ?? 'Unknown Student';
        $studentSection = 'Unassigned Section';
        $studentGrade   = 'Unassigned Grade';
        $academicYear   = '2026-2027';

        // Register Parent User
        $parent = User::create([
            'name'            => trim($request->name),
            'email'           => strtolower(trim($request->email)),
            'password'        => Hash::make($request->password),
            'role'            => 'parent',
            'student_name'    => $studentName,
            'student_grade'   => $studentGrade,
            'student_section' => $studentSection,
            'academic_year'   => $academicYear,
        ]);

        $token = $parent->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $parent,
            'token' => $token,
        ]);
    }

    private function namesMatch($name1, $name2)
    {
        return $this->studentNamesMatch($name1, $name2);
    }

    private function normalizeForMatching($text)
    {
        $text = strtolower(trim($text));

        // Handle "Last, First" format by reversing it
        if (str_contains($text, ',')) {
            $parts = explode(',', $text);
            $lastName = trim($parts[0]);
            $firstAndMiddle = trim($parts[1]);
            $text = $firstAndMiddle . ' ' . $lastName;
        }

        // Remove punctuation, hyphens, underscores
        $text = preg_replace('/[.,\-_]/', ' ', $text);

        // Normalize common OCR character confusions
        $ocrSource = ['0', 'l', '|', '!', '8', '5'];
        $ocrTarget = ['o', 'i', 'i', 'i', 'b', 's'];
        $text = str_replace($ocrSource, $ocrTarget, $text);

        // Collapsing multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function studentNamesMatch($name1, $name2)
    {
        $n1 = $this->normalizeForMatching($name1);
        $n2 = $this->normalizeForMatching($name2);

        if ($n1 === $n2) {
            return true;
        }

        $words1 = array_filter(explode(' ', $n1), function ($w) { return strlen($w) > 1; });
        $words2 = array_filter(explode(' ', $n2), function ($w) { return strlen($w) > 1; });

        if (empty($words1) || empty($words2)) {
            return false;
        }

        $matchedCount = 0;
        $usedKeys2 = [];

        foreach ($words1 as $w1) {
            foreach ($words2 as $k2 => $w2) {
                if (in_array($k2, $usedKeys2)) {
                    continue;
                }

                if ($w1 === $w2) {
                    $matchedCount++;
                    $usedKeys2[] = $k2;
                    break;
                }

                $dist = levenshtein($w1, $w2);
                $maxLen = max(strlen($w1), strlen($w2));
                $allowedDist = $maxLen <= 4 ? 1 : 2;

                if ($dist <= $allowedDist) {
                    $matchedCount++;
                    $usedKeys2[] = $k2;
                    break;
                }
            }
        }

        $minRequired = min(2, count($words1), count($words2));
        return $matchedCount >= $minRequired && $matchedCount >= (min(count($words1), count($words2)) * 0.7);
    }

    private function sectionsMatch($sec1, $sec2)
    {
        $s1 = $this->normalizeForMatching($sec1);
        $s2 = $this->normalizeForMatching($sec2);

        if ($s1 === $s2) {
            return true;
        }

        // Remove 'grade' word to compare section identifier
        $s1 = str_replace('grade', '', $s1);
        $s2 = str_replace('grade', '', $s2);

        // Remove spaces
        $s1 = str_replace(' ', '', $s1);
        $s2 = str_replace(' ', '', $s2);

        if ($s1 === $s2) {
            return true;
        }

        // Fuzzy match on section
        $dist = levenshtein($s1, $s2);
        $maxLen = max(strlen($s1), strlen($s2));
        if ($maxLen > 0 && (1 - $dist / $maxLen) >= 0.8) {
            return true;
        }

        return false;
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar_url' => 'required|string']);

        $user = $request->user();
        $user->update(['avatar_url' => $request->avatar_url]);

        return response()->json($user);
    }
}
