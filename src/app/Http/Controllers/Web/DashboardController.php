<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysis;
use App\Models\Report;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user?->parent_guardian && !$user?->staff) {
            return $this->parentDashboard($user);
        }

        $now = now();

        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();

        $previousWeekStart = $now->copy()->subWeek()->startOfWeek();
        $previousWeekEnd = $now->copy()->subWeek()->endOfWeek();

        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $schoolYearStart = $now->month >= 6
            ? $now->copy()->startOfYear()->addMonths(5)
            : $now->copy()->subYear()->startOfYear()->addMonths(5);

        $schoolYearEnd = $schoolYearStart->copy()->addMonths(9)->endOfMonth();

        $academicYearReported = Report::whereBetween('reports.created_at', [$schoolYearStart, $schoolYearEnd]);

        $weeklyReported = Report::whereBetween('reports.created_at', [$weekStart, $weekEnd])->count();

        $previousWeeklyReported = Report::whereBetween('reports.created_at', [$previousWeekStart, $previousWeekEnd])->count();

        $monthlyReported = Report::whereBetween('reports.created_at', [$monthStart, $monthEnd])->count();

        $previousMonthlyReported = Report::whereBetween('reports.created_at', [$previousMonthStart, $previousMonthEnd])->count();

        $weeklyResolved = Report::whereBetween('reports.updated_at', [$weekStart, $weekEnd])
            ->whereHas(
                'current_status',
                fn($q) => $q->where('status_name', 'Resolved')
            )
            ->count();

        $ongoingReports = Report::whereHas(
            'current_status',
            fn($q) => $q->whereNotIn(
                'status_name',
                ['Resolved', 'Dismissed']
            )
        )->count();

        $resolvedReports = (clone $academicYearReported)
            ->whereHas(
                'current_status',
                fn($q) => $q->where('status_name', 'Resolved')
            )
            ->count();

        $academicYearTotal = $academicYearReported->count();

        $resolutionRate = $academicYearTotal > 0
            ? round(($resolvedReports / $academicYearTotal) * 100, 1)
            : 0;

        $openRate = $academicYearTotal > 0
        ? round(
            (
                $academicYearTotal
                - $resolvedReports
                - (clone $academicYearReported)
                    ->whereHas('current_status', function ($q) {
                        $q->where('status_name', 'Dismissed');
                    })
                    ->count()
            ) / $academicYearTotal * 100,
            1
        )
        : 0;

        $percentageChange = function (int $current, int $previous): ?float {
            if ($previous === 0) {
                return $current > 0 ? 100 : 0;
            }

            return round(
                (($current - $previous) / $previous) * 100,
                1
            );
        };

        $topCategories = (clone $academicYearReported)
            ->select(
                'incident_categories.category_name',
                DB::raw('COUNT(*) as total')
            )
            ->join(
                'incident_categories',
                'reports.category_id',
                '=',
                'incident_categories.id'
            )
            ->groupBy(
                'incident_categories.category_name'
            )
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($item) use ($academicYearTotal) {
                return [
                    'category_name' => $item->category_name,
                    'total' => (int) $item->total,
                    'percentage' => $academicYearTotal > 0
                        ? round(
                            ($item->total / $academicYearTotal) * 100,
                            1
                        )
                        : 0,
                ];
            })
            ->values();

        $statusDistribution = (clone $academicYearReported)
            ->select(
                'report_statuses.status_name',
                DB::raw('COUNT(*) as total')
            )
            ->join(
                'report_statuses',
                'reports.current_status_id',
                '=',
                'report_statuses.id'
            )
            ->groupBy(
                'report_statuses.status_name'
            )
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($academicYearTotal) {
                return [
                    'status_name' => $item->status_name,
                    'total' => (int) $item->total,
                    'percentage' => $academicYearTotal > 0
                        ? round(
                            ($item->total / $academicYearTotal) * 100,
                            1
                        )
                        : 0,
                ];
            })
            ->values();

        $latestPosition = $request->user()?->staff->positions()
            ->orderByDesc('staff_position.assigned_at')
            ->first();

        $schoolYear = SchoolYear::where('is_active', true)->latest()->first();

        $latestAnalysis = $latestPosition
            ? AiAnalysis::where('position_id', $latestPosition->id)
                ->where('school_year_id', optional($schoolYear)->id)
                ->orderByDesc('created_at')
                ->first()
            : null;

        $analysisFilter = $request->query('filter', 'all');

        $analyses = AiAnalysis::query()->when($latestPosition, fn($query) => $query->where(
                    'position_id',
                    $latestPosition->id
                )
            )
            ->when(!$latestPosition, fn($query) => $query->whereRaw('0 = 1'))
            ->where('school_year_id', optional($schoolYear)->id)
            ->when(in_array($analysisFilter, ['weekly', 'monthly', 'yearly']),
                fn($query) => $query->where('periodicity', $analysisFilter)
            )
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $dashboardMetrics = [
            'weekly_reported' => $weeklyReported,
            'weekly_resolved' => $weeklyResolved,
            'weekly_change' => $percentageChange($weeklyReported, $previousWeeklyReported),
            'monthly_reported' => $monthlyReported,
            'monthly_change' => $percentageChange($monthlyReported, $previousMonthlyReported),
            'academic_year_reported' => $academicYearTotal,
            'ongoing_reports' => $ongoingReports,
            'resolved_reports' => $resolvedReports,
            'resolution_rate' => $resolutionRate,
            'open_rate' => $openRate,
            'academic_year_label' => $schoolYearStart->format('Y') . '–' . $schoolYearEnd->format('Y'),
        ];

        return Inertia::render('dashboard',
            [
                'dashboardMetrics' => $dashboardMetrics,
                'topCategories' => $topCategories,
                'statusDistribution' => $statusDistribution,
                'latestAnalysis' => $latestAnalysis,
                'analyses' => $analyses,
                'analysisFilter' => $analysisFilter,
            ]
        );
    }

    // UNIQUE DASHBOARD FOR PARENT USERS
    private function parentDashboard(User $user)
    {
        $parent = $user->parent_guardian;

        abort_unless($parent, 403);

        $now = now();

        $schoolYearStart = $now->month >= 6
            ? $now->copy()->startOfYear()->addMonths(5)
            : $now->copy()->subYear()->startOfYear()->addMonths(5);

        $schoolYearEnd = $schoolYearStart->copy()->addMonths(9)->endOfMonth();

        $relatedStudents = $parent->students()
            ->with(['latestEnrollment.grade_section'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $relatedStudentIds = $relatedStudents->pluck('id');

        $relatedReports = Report::query()
            ->whereBetween('reports.created_at', [$schoolYearStart, $schoolYearEnd])
            ->when(
                $relatedStudentIds->isNotEmpty(),
                fn ($query) => $query->whereHas(
                    'students',
                    fn ($studentQuery) => $studentQuery->whereIn('students.id', $relatedStudentIds)
                ),
                fn ($query) => $query->whereRaw('0 = 1')
            );

        $academicYearTotal = (clone $relatedReports)->count();

        $ongoingCases = (clone $relatedReports)
            ->whereHas(
                'current_status',
                fn ($query) => $query->whereNotIn('status_name', ['Resolved', 'Dismissed'])
            )
            ->count();

        $resolvedCases = (clone $relatedReports)
            ->whereHas(
                'current_status',
                fn ($query) => $query->where('status_name', 'Resolved')
            )
            ->count();

        $resolutionRate = $academicYearTotal > 0
            ? round(($resolvedCases / $academicYearTotal) * 100, 1)
            : 0;

        $submittedReports = Report::query()
            ->where('reported_by', $user->id)
            ->whereBetween('created_at', [$schoolYearStart, $schoolYearEnd])
            ->count();

        $statusDistribution = (clone $relatedReports)
            ->select(
                'report_statuses.status_name',
                DB::raw('COUNT(reports.id) as total')
            )
            ->join(
                'report_statuses',
                'reports.current_status_id',
                '=',
                'report_statuses.id'
            )
            ->groupBy('report_statuses.status_name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($academicYearTotal) {
                return [
                    'status_name' => $item->status_name,
                    'total' => (int) $item->total,
                    'percentage' => $academicYearTotal > 0
                        ? round(($item->total / $academicYearTotal) * 100, 1)
                        : 0,
                ];
            })
            ->values();

        $recentCases = (clone $relatedReports)
            ->with([
                'category:id,category_name',
                'current_status:id,status_name',
                'students' => fn ($query) => $query
                    ->select('students.id', 'students.first_name', 'students.last_name')
                    ->whereIn('students.id', $relatedStudentIds),
            ])
            ->orderByDesc('reports.created_at')
            ->take(6)
            ->get()
            ->map(function ($report) {
                $student = $report->students->first();

                return [
                    'id' => $report->id,
                    'report_code' => $report->report_code,
                    'incident_title' => $report->incident_title,
                    'student_name' => $student
                        ? trim("{$student->first_name} {$student->last_name}")
                        : 'Related student',
                    'category_name' => $report->category?->category_name ?? 'Incident',
                    'status_name' => $report->current_status?->status_name ?? 'Unknown',
                    'incident_date' => $report->incident_date?->format('Y-m-d'),
                    'incident_time' => $report->incident_time?->format('H:i'),
                    'created_at' => $report->created_at?->toIso8601String(),
                ];
            })
            ->values();

        $relatedStudentsView = $relatedStudents->map(function ($student) use ($relatedReports) {
            $caseCount = (clone $relatedReports)
                ->whereHas(
                    'students',
                    fn ($query) => $query->where('students.id', $student->id)
                )
                ->count();

            return [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'name' => trim("{$student->first_name} {$student->last_name}"),
                'grade_level' => $student->latestEnrollment?->grade_section?->grade_level,
                'section' => $student->latestEnrollment?->grade_section?->section,
                'status' => $student->status,
                'case_count' => $caseCount,
            ];
        })->values();

        $dashboardMetrics = [
            'related_students' => $relatedStudents->count(),
            'submitted_reports' => $submittedReports,
            'ongoing_cases' => $ongoingCases,
            'resolved_cases' => $resolvedCases,
            'resolution_rate' => $resolutionRate,
            'academic_year_label' => $schoolYearStart->format('Y') . '–' . $schoolYearEnd->format('Y'),
        ];

        return Inertia::render('ParentDashboard', [
            'dashboardMetrics' => $dashboardMetrics,
            'relatedStudents' => $relatedStudentsView,
            'recentCases' => $recentCases,
            'statusDistribution' => $statusDistribution,
        ]);
    }
}
