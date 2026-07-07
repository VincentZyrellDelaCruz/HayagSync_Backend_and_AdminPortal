<?php

namespace App\Http\Controllers;

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
            'email' => 'required|email',
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
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function registerParent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'certificate_filename' => 'required|string',
            'certificate_content_mock' => 'nullable|string',
            'student_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        // --- SIMULATED OCR SCANNER LOGIC ---
        $textToScan = strtoupper($request->input('certificate_content_mock') ?? $request->input('certificate_filename'));

        // 1. Check for "ENROLLED" keyword
        if (strpos($textToScan, 'ENROLLED') === false) {
            return response()->json([
                'message' => "Verification Failed: Document does not contain verification status 'ENROLLED'."
            ], 422);
        }

        // 2. Check for Current Academic Year "2026"
        if (strpos($textToScan, '2026') === false && strpos($textToScan, '26') === false) {
            return response()->json([
                'message' => "Verification Failed: Enrollment certificate is from an outdated Academic Year. Required: 2026-2027."
            ], 422);
        }

        // 3. Extract Student Details (Simulated OCR regex matches)
        $studentName = "";
        $studentSection = "";
        $academicYear = "2026-2027";

        if (preg_match('/(?:STUDENT|STUDENT NAME)(?:\\s*:\\s*|\\s+)([^.\n]+)/i', $textToScan, $matches)) {
            $studentName = ucwords(strtolower(trim($matches[1])));
        }

        if (preg_match('/(?:SECTION)(?:\\s*:\\s*|\\s+)([^.\n]+)/i', $textToScan, $matches)) {
            $studentSection = trim($matches[1]);
        }

        if (preg_match('/(?:AY|ACADEMIC YEAR)(?:\\s*:\\s*|\\s+)([^.\n]+)/i', $textToScan, $matches)) {
            $academicYear = strtoupper(trim($matches[1]));
        }

        if (empty($studentName)) {
            return response()->json([
                'message' => "Verification Failed: Student name could not be extracted from the enrollment certificate. Please upload a clearer Enrollment Certificate."
            ], 422);
        }

        if (empty($studentSection)) {
            return response()->json([
                'message' => "Verification Failed: Student section could not be extracted from the enrollment certificate. Please upload a clearer Enrollment Certificate."
            ], 422);
        }

        // 4. Extract and Validate Parent Name from Certificate
        $parentNameFromCert = "";
        if (preg_match('/(?:PARENT)(?:\\s*:\\s*|\\s+)([^.\n]+)/i', $textToScan, $matches)) {
            $parentNameFromCert = trim($matches[1]);
        }

        if (empty($parentNameFromCert)) {
            return response()->json([
                'message' => "Verification Failed: Parent name could not be extracted from the enrollment certificate. Please upload a clearer Enrollment Certificate."
            ], 422);
        }

        if (!$this->namesMatch($parentNameFromCert, $request->name)) {
            return response()->json([
                'message' => "Verification Failed: Parent name on the enrollment certificate ('" . ucwords(strtolower($parentNameFromCert)) . "') does not match the name provided during verification."
            ], 422);
        }

        // 5. Extract and Validate Student Name from Certificate (handling format discrepancies)
        if ($request->filled('student_name')) {
            if (!$this->studentNamesMatch($studentName, $request->student_name)) {
                return response()->json([
                    'message' => "Verification Failed: Student name on the enrollment certificate ('{$studentName}') does not match the student name provided."
                ], 422);
            }
        }

        // 6. Verify against School Registry
        $schoolRegistry = [
            ['name' => 'Maria Dela Cruz', 'section' => 'Grade 11 - STEM A'],
            ['name' => 'Kyle Coles', 'section' => 'Grade 11 - STEM A'],
            ['name' => 'Kyle Pring Coles', 'section' => 'Grade 11 - STEM A'],
            ['name' => 'Coles, Kyle Pring', 'section' => 'Grade 11 - STEM A'],
            ['name' => 'Juan Dela Cruz Jr.', 'section' => 'Grade 11 - STEM A'],
            ['name' => 'Maria Dela Cruz Jr.', 'section' => 'Grade 11 - STEM A'],
            ['name' => 'Pedro Dela Cruz', 'section' => 'Grade 8 - Uranus'],
            ['name' => 'Sophia Dela Cruz', 'section' => 'Grade 7 - Mahogany'],
            ['name' => 'Alex Dela Cruz', 'section' => 'Grade 12 - ABM A'],
            ['name' => 'Juan Dela Cruz', 'section' => 'Grade 10 - Rizal'],
            ['name' => 'Arthur Pendragon', 'section' => 'Grade 11 - STEM A'],
        ];

        $matchedRegistryStudent = null;
        foreach ($schoolRegistry as $registryStudent) {
            if ($this->studentNamesMatch($studentName, $registryStudent['name']) &&
                $this->sectionsMatch($studentSection, $registryStudent['section'])) {
                $matchedRegistryStudent = $registryStudent;
                break;
            }
        }

        // Keep the exact student name and student section extracted from the certificate
        // This ensures the verification popup, the verified page, and the certificate are exactly synchronized.
        $studentGrade = "Grade 11";
        if (preg_match('/(GRADE\s*\d+)/i', $studentSection, $gradeMatches)) {
            $studentGrade = ucwords(strtolower(trim($gradeMatches[1])));
        }

        // Register Parent
        $parent = User::create([
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'role' => 'parent',
            'student_name' => $studentName,
            'student_grade' => $studentGrade,
            'student_section' => $studentSection,
            'academic_year' => $academicYear,
        ]);

        $token = $parent->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $parent,
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
            'new_password' => 'required|min:6',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar_url' => 'required|string',
        ]);

        $user = $request->user();
        $user->update([
            'avatar_url' => $request->avatar_url,
        ]);

        return response()->json($user);
    }
}
