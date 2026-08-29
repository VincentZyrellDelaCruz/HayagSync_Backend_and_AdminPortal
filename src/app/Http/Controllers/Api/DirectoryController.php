<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function getParents(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'parent') {
            return response()->json(['message' => 'Parents cannot access directory.'], 403);
        }

        if ($user->role === 'adviser') {
            // Adviser only sees parents of their sections
            $sectionIds = $user->staff?->section_advisers->pluck('id') ?? [];
            $students   = Student::whereIn('grade_section_id', $sectionIds)->with('parent_guardians')->get();

            $parents = $students->flatMap(fn($s) => $s->parent_guardians)->unique('id')->values();
            return response()->json($parents);
        }

        // Principal & OSD see all parents
        $parents = User::where('role', 'parent')->get();
        return response()->json($parents);
    }
}
