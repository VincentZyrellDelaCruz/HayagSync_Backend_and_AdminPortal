<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentParentGuardianController extends Controller
{
    public function showAllStudent(): JsonResponse
    {
        // $school = array_unique(Auth::user()->parent_guardian()->students()->school_id);

        $students = Student::all();

        return response()->json($students);
    }

    public function showAllRelatedStudent(): JsonResponse
    {
        $pg_user_id = Auth::user()->id;

        $students = ParentGuardian::with('students')->where('user_id', $pg_user_id)->get();

        return response()->json($students);
    }
}
