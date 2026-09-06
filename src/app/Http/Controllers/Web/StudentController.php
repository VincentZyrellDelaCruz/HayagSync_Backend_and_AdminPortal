<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GradeSection;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim($request->get('search', ''));
        $grade    = trim($request->get('grade', ''));
        $section  = trim($request->get('section', ''));

        $user = Auth::user();

        $isAdviser = $user->staff && $user->staff->latestPosition?->position_name === 'Teacher';

        $sectionQuery = GradeSection::query();

        if ($isAdviser) {
            $sectionQuery->where(
                'adviser',
                $user->staff->user_id
            );
        }

        if ($grade !== '' && strtolower($grade) !== 'alumni') {
            $sectionQuery->where(
                'grade_level',
                $grade
            );
        }

        $sections = $sectionQuery->select([
                'id',
                'grade_level',
                'section',
            ])->orderBy('grade_level')->orderBy('section')->get();

        $gradeQuery = GradeSection::query();

        if ($isAdviser) {
            $gradeQuery->where(
                'adviser',
                $user->staff->user_id
            );
        }

        $grades = $gradeQuery->select('grade_level')->distinct()->orderBy('grade_level')->pluck('grade_level')->values();

        $query = Student::query()->with(['latestEnrollment.grade_section',]);

        if ($isAdviser) {
            $query->whereHas(
                'latestEnrollment.grade_section',
                function ($q) use ($user) {
                    $q->where(
                        'adviser',
                        $user->staff->user_id
                    );
                }
            );

            // Adviser should only see currently enrolled students.
            $query->whereHas(
                'latestEnrollment',
                function ($q) {
                    $q->whereNull('ended_at');
                }
            );
        }

        if (strtolower($grade) === 'alumni') {
            $query->whereHas('latestEnrollment', function ($q) {
                $q->whereNotNull('ended_at');
            });

            // Section does not apply to the Alumni filter.
            $section = '';
        } else {
            $query->whereHas(
                'latestEnrollment',
                function ($q) use ($grade, $section) {
                    // Latest enrollment must still be active.
                    $q->whereNull('ended_at');

                    $q->whereHas(
                        'grade_section',
                        function ($sectionQuery) use (
                            $grade,
                            $section
                        ) {
                            if ($grade !== '') {
                                $sectionQuery->where(
                                    'grade_level',
                                    $grade
                                );
                            }

                            if ($section !== '') {
                                $sectionQuery->where(
                                    'section',
                                    $section
                                );
                            }
                        }
                    );
                }
            );
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'first_name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'last_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'student_number',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->paginate(10)->withQueryString();

        $students->getCollection()->transform(
            function ($student) {
                $student->latest_section =
                    $student->latestEnrollment?->grade_section;

                $student->is_alumni =
                    $student->latestEnrollment?->ended_at !== null;

                unset($student->latestEnrollment);

                return $student;
            }
        );

        // $sections = GradeSection::all();

        return Inertia::render('Students/Index',
            [
                'students' => $students,
                'sections' => $sections,
                'grades' => $grades,
                'search' => $search,
                'grade' => $grade,
                'section' => $section,
            ]
        );
    }

    public function show(Student $student)
    {
        $student->load([
            'parent_guardians.user',
            'reports.category',
            'disciplinary_actions.staff.user',
            'grade_sections.school_year',
            'latestEnrollment.grade_section.school_year',
        ]);

        // return dd($student->latestEnrollment->grade_section->school_year->school_year);

        return Inertia::render('Students/StudentInfo', compact('student'));
    }
}
