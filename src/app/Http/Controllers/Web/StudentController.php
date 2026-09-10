<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GradeSection;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->parent_guardian && !$user->staff) {
            return $this->parentIndex($request, $user);
        }

        $search = trim($request->get('search', ''));
        $grade = trim($request->get('grade', ''));
        $section = trim($request->get('section', ''));

        $isAdviser = $user->staff && $user->staff->latestPosition?->position_name === 'Teacher';

        $sectionQuery = GradeSection::query();

        if ($isAdviser) {
            $sectionQuery->where('adviser', $user->staff->user_id);
        }

        if ($grade !== '' && strtolower($grade) !== 'alumni') {
            $sectionQuery->where('grade_level', $grade);
        }

        $sections = $sectionQuery
            ->select(['id', 'grade_level', 'section'])
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get();

        $gradeQuery = GradeSection::query();

        if ($isAdviser) {
            $gradeQuery->where('adviser', $user->staff->user_id);
        }

        $grades = $gradeQuery
            ->select('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level')
            ->values();

        $query = Student::query()->with(['latestEnrollment.grade_section']);

        if ($isAdviser) {
            $query->whereHas('latestEnrollment.grade_section', fn ($q) => $q->where('adviser', $user->staff->user_id));

            $query->whereHas('latestEnrollment', fn ($q) => $q->whereNull('ended_at'));
        }

        if (strtolower($grade) === 'alumni') {
            $query->whereHas('latestEnrollment', fn ($q) => $q->whereNotNull('ended_at'));
            $section = '';
        } else {
            $query->whereHas('latestEnrollment', function ($q) use ($grade, $section) {
                $q->whereNull('ended_at');

                $q->whereHas('grade_section', function ($sectionQuery) use ($grade, $section) {
                    if ($grade !== '') {
                        $sectionQuery->where('grade_level', $grade);
                    }

                    if ($section !== '') {
                        $sectionQuery->where('section', $section);
                    }
                });
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->paginate(10)->withQueryString();

        $students->getCollection()->transform(function ($student) {
            $student->latest_section = $student->latestEnrollment?->grade_section;
            $student->is_alumni = $student->latestEnrollment?->ended_at !== null;
            unset($student->latestEnrollment);

            return $student;
        });

        return Inertia::render('Students/Index', [
            'students' => $students,
            'sections' => $sections,
            'grades' => $grades,
            'search' => $search,
            'grade' => $grade,
            'section' => $section,
            'isParent' => false,
        ]);
    }

    private function parentIndex(Request $request, $user)
    {
        $parent = $user->parent_guardian;

        abort_unless($parent, 403);

        $search = trim($request->get('search', ''));
        $grade = trim($request->get('grade', ''));
        $section = trim($request->get('section', ''));

        // Explicitly deny exact searches for an existing unrelated student ID.
        if ($search !== '') {
            $unrelatedStudentExists = Student::query()
                ->where('student_number', $search)
                ->whereDoesntHave('parent_guardians', fn ($q) => $q->where('parent_guardians.user_id', $user->id))
                ->exists();

            if ($unrelatedStudentExists) {
                abort(403, 'You are not authorized to access this student record.');
            }
        }

        $catalogStudents = $parent->students()
            ->with(['latestEnrollment.grade_section'])
            ->get();

        $sectionModels = $catalogStudents
            ->map(fn ($student) => $student->latestEnrollment?->grade_section)
            ->filter()
            ->unique(fn ($item) => "{$item->grade_level}|{$item->section}")
            ->sortBy(fn ($item) => "{$item->grade_level}|{$item->section}")
            ->values();

        $sections = $sectionModels->map(fn ($item) => [
            'id' => $item->id,
            'grade_level' => $item->grade_level,
            'section' => $item->section,
        ])->values();

        $grades = $catalogStudents
            ->map(fn ($student) => $student->latestEnrollment?->grade_section?->grade_level)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $query = $parent->students()->with(['latestEnrollment.grade_section']);

        if (strtolower($grade) === 'alumni') {
            $query->whereHas('latestEnrollment', fn ($q) => $q->whereNotNull('ended_at'));
            $section = '';
        } else {
            $query->whereHas('latestEnrollment', function ($q) use ($grade, $section) {
                $q->whereNull('ended_at');

                $q->whereHas('grade_section', function ($sectionQuery) use ($grade, $section) {
                    if ($grade !== '') {
                        $sectionQuery->where('grade_level', $grade);
                    }

                    if ($section !== '') {
                        $sectionQuery->where('section', $section);
                    }
                });
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $students = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        $students->getCollection()->transform(function ($student) {
            $student->latest_section = $student->latestEnrollment?->grade_section;
            $student->is_alumni = $student->latestEnrollment?->ended_at !== null;
            unset($student->latestEnrollment);

            return $student;
        });

        return Inertia::render('Students/Index', [
            'students' => $students,
            'sections' => $sections,
            'grades' => $grades,
            'search' => $search,
            'grade' => $grade,
            'section' => $section,
            'isParent' => true,
        ]);
    }

    public function show(Student $student)
    {
        Gate::authorize('view', $student);

        $user = Auth::user();

        // Parent/guardian receives a deliberately limited student view.
        if ($user->parent_guardian && !$user->staff) {
            $student->load(['latestEnrollment.grade_section.school_year']);

            return Inertia::render('Students/ParentStudentInfo', compact('student'));
        }

        // Existing staff detail behavior remains unchanged.
        $student->load([
            'parent_guardians.user',
            'reports.category',
            'disciplinary_actions.staff.user',
            'grade_sections.school_year',
            'latestEnrollment.grade_section.school_year',
        ]);

        return Inertia::render('Students/StudentInfo', compact('student'));
    }
}
