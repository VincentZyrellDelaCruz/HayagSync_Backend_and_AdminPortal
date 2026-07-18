<?php

namespace App\Http\Controllers;

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
            $allParents = User::where('role', 'parent')->get();
            $parents = $allParents->filter(function($parent) use ($user) {
                return $this->sectionsMatch($parent->student_section, $user->assigned_section);
            });
            return response()->json($parents->values());
        } else {
            // Principal & OSD view all parents globally
            $parents = User::where('role', 'parent')->get();
            return response()->json($parents);
        }
    }

    private function sectionsMatch($s1, $s2)
    {
        $normalize = function($s) {
            $s = strtolower(trim($s ?? ''));
            $s = str_replace(['-', ' '], '', $s);
            return $s;
        };
        return $normalize($s1) === $normalize($s2);
    }
}
