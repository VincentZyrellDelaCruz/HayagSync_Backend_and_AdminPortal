<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GradeSection;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $grade    = $request->get('grade');
        $section  = $request->get('section');

        $query = Student::with('grade_sections');

        // Restrict Adviser (Teacher) to only their sections
        $user = Auth::user();
        if ($user->staff && $user->staff->latestPosition()?->position_name === 'Teacher') {
            // Get all GradeSection IDs where this staff is the adviser
            $sectionIds = GradeSection::where('adviser', $user->staff->id)
                ->pluck('id')
                ->toArray();

            if (!empty($sectionIds)) {
                $query->whereHas('grade_sections', function ($q) use ($user) {
                    $q->where('adviser', $user->staff->id);
                });
            } else {
                // If teacher has no advised sections, return empty result
                $query->whereRaw('0 = 1');
            }
        }

        // Apply filters
        if ($grade) {
            $query->whereHas('grade_sections', fn($q) => $q->where('grade_level', $grade));
        }
        if ($section) {
            $query->whereHas('grade_sections', fn($q) => $q->where('section', $section));
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(10);
        $sections = GradeSection::all();

        return view('students.index', compact('students', 'search', 'grade', 'section', 'sections'));
    }

    public function show(Student $student)
    {
        $student->load([
            'parent_guardians',
            'reports.category',
            'disciplinary_actions.staff.user',
            'grade_sections.school_year',
        ]);

        return view('students.student-info', compact('student'));
    }
}
