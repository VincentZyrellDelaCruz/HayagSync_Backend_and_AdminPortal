<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysis;
use App\Models\Report;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $schoolYear = Cache::remember(
            'dashboard:active-school-year',
            now()->addMinutes(10),
            fn () => SchoolYear::query()->where('is_active', true)->latest()->first(['id', 'school_year'])
        );

        $resolvedStatusId = Cache::remember(
            'dashboard:resolved-status-id',
            now()->addHour(),
            fn () => DB::table('report_statuses')->where('status_name', 'Resolved')->value('id')
        );

        $dismissedStatusId = Cache::remember(
            'dashboard:dismissed-status-id',
            now()->addHour(),
            fn () => DB::table('report_statuses')->where('status_name', 'Dismissed')->value('id')
        );

        /*
         * Dashboard aggregate metrics are cached briefly because all staff
         * users see the same school-wide metrics.
         */
        $statsKey = sprintf(
            'dashboard:staff:stats:%s:%s:%s',
            $schoolYearStart->toDateString(),
            $weekStart->toDateString(),
            $monthStart->toDateString()
        );

        $stats = Cache::remember($statsKey, now()->addSeconds(20), function () use (
            $weekStart,
            $weekEnd,
            $previousWeekStart,
            $previousWeekEnd,
            $monthStart,
            $monthEnd,
            $previousMonthStart,
            $previousMonthEnd,
            $schoolYearStart,
            $schoolYearEnd,
            $resolvedStatusId,
            $dismissedStatusId
        ) {
            $row = DB::table('reports')
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) FILTER (WHERE created_at BETWEEN ? AND ?) AS weekly_reported,
                    COUNT(*) FILTER (WHERE created_at BETWEEN ? AND ?) AS previous_weekly_reported,
                    COUNT(*) FILTER (WHERE created_at BETWEEN ? AND ?) AS monthly_reported,
                    COUNT(*) FILTER (WHERE created_at BETWEEN ? AND ?) AS previous_monthly_reported,
                    COUNT(*) FILTER (WHERE created_at BETWEEN ? AND ? AND current_status_id = ?) AS resolved_academic_year,
                    COUNT(*) FILTER (WHERE created_at BETWEEN ? AND ?) AS academic_year_total,
                    COUNT(*) FILTER (WHERE created_at BETWEEN ? AND ? AND current_status_id = ?) AS dismissed_academic_year,
                    COUNT(*) FILTER (WHERE updated_at BETWEEN ? AND ? AND current_status_id = ?) AS weekly_resolved,
                    COUNT(*) FILTER (WHERE current_status_id NOT IN (?, ?)) AS ongoing_reports
                ', [
                    $weekStart, $weekEnd,
                    $previousWeekStart, $previousWeekEnd,
                    $monthStart, $monthEnd,
                    $previousMonthStart, $previousMonthEnd,
                    $schoolYearStart, $schoolYearEnd, $resolvedStatusId,
                    $schoolYearStart, $schoolYearEnd,
                    $schoolYearStart, $schoolYearEnd, $dismissedStatusId,
                    $weekStart, $weekEnd, $resolvedStatusId,
                    $resolvedStatusId, $dismissedStatusId,
                ])
                ->first();

            return [
                'weekly_reported' => (int) $row->weekly_reported,
                'previous_weekly_reported' => (int) $row->previous_weekly_reported,
                'monthly_reported' => (int) $row->monthly_reported,
                'previous_monthly_reported' => (int) $row->previous_monthly_reported,
                'resolved_academic_year' => (int) $row->resolved_academic_year,
                'academic_year_total' => (int) $row->academic_year_total,
                'dismissed_academic_year' => (int) $row->dismissed_academic_year,
                'weekly_resolved' => (int) $row->weekly_resolved,
                'ongoing_reports' => (int) $row->ongoing_reports,
            ];
        });

        $percentageChange = function (int $current, int $previous): ?float {
            if ($previous === 0) return $current > 0 ? 100 : 0;
            return round((($current - $previous) / $previous) * 100, 1);
        };

        $academicYearTotal = $stats['academic_year_total'];
        $resolvedReports = $stats['resolved_academic_year'];
        $dismissedReports = $stats['dismissed_academic_year'];

        $resolutionRate = $academicYearTotal > 0
            ? round(($resolvedReports / $academicYearTotal) * 100, 1)
            : 0;

        $openRate = $academicYearTotal > 0
            ? round((($academicYearTotal - $resolvedReports - $dismissedReports) / $academicYearTotal) * 100, 1)
            : 0;

        $topCategories = Cache::remember(
            "dashboard:staff:categories:{$schoolYearStart->toDateString()}",
            now()->addSeconds(30),
            fn () => Report::query()
                ->whereBetween('reports.created_at', [$schoolYearStart, $schoolYearEnd])
                ->withTrashed()
                ->whereNull('reports.deleted_at')
                ->join('incident_categories', 'reports.category_id', '=', 'incident_categories.id')
                ->select('incident_categories.category_name', DB::raw('COUNT(*) AS total'))
                ->groupBy('incident_categories.category_name')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(fn ($item) => [
                    'category_name' => $item->category_name,
                    'total' => (int) $item->total,
                    'percentage' => $academicYearTotal > 0 ? round(((int) $item->total / $academicYearTotal) * 100, 1) : 0,
                ])
                ->values()
        );

        $statusDistribution = Cache::remember(
            "dashboard:staff:statuses:{$schoolYearStart->toDateString()}",
            now()->addSeconds(30),
            fn () => Report::query()
                ->whereBetween('reports.created_at', [$schoolYearStart, $schoolYearEnd])
                ->join('report_statuses', 'reports.current_status_id', '=', 'report_statuses.id')
                ->select('report_statuses.status_name', DB::raw('COUNT(*) AS total'))
                ->groupBy('report_statuses.status_name')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($item) => [
                    'status_name' => $item->status_name,
                    'total' => (int) $item->total,
                    'percentage' => $academicYearTotal > 0 ? round(((int) $item->total / $academicYearTotal) * 100, 1) : 0,
                ])
                ->values()
        );

        $staff = $user?->staff;

        $latestPosition = $staff
            ? Cache::remember(
                "staff:{$staff->getKey()}:latest-position",
                now()->addMinutes(5),
                fn () => $staff->positions()
                    ->select('positions.id', 'positions.position_name')
                    ->orderByDesc('staff_position.assigned_at')
                    ->first()
            )
            : null;

        $analysisFilter = $request->query('filter', 'all');

        $analyses = AiAnalysis::query()
            ->select(['id', 'periodicity', 'period_label', 'output', 'position_id', 'school_year_id', 'created_at'])
            ->when($latestPosition, fn ($query) => $query->where('position_id', $latestPosition->id))
            ->when(!$latestPosition, fn ($query) => $query->whereRaw('0 = 1'))
            ->where('school_year_id', optional($schoolYear)->id)
            ->when(
                in_array($analysisFilter, ['weekly', 'monthly', 'yearly'], true),
                fn ($query) => $query->where('periodicity', $analysisFilter)
            )
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $latestAnalysis = $latestPosition
            ? AiAnalysis::query()
                ->select(['id', 'periodicity', 'period_label', 'output', 'position_id', 'school_year_id', 'created_at'])
                ->where('position_id', $latestPosition->id)
                ->where('school_year_id', optional($schoolYear)->id)
                ->orderByDesc('created_at')
                ->first()
            : null;

        $dashboardMetrics = [
            'weekly_reported' => $stats['weekly_reported'],
            'weekly_resolved' => $stats['weekly_resolved'],
            'weekly_change' => $percentageChange($stats['weekly_reported'], $stats['previous_weekly_reported']),
            'monthly_reported' => $stats['monthly_reported'],
            'monthly_change' => $percentageChange($stats['monthly_reported'], $stats['previous_monthly_reported']),
            'academic_year_reported' => $academicYearTotal,
            'ongoing_reports' => $stats['ongoing_reports'],
            'resolved_reports' => $resolvedReports,
            'resolution_rate' => $resolutionRate,
            'open_rate' => $openRate,
            'academic_year_label' => $schoolYearStart->format('Y') . '–' . $schoolYearEnd->format('Y'),
        ];

        return Inertia::render('dashboard', [
            'dashboardMetrics' => $dashboardMetrics,
            'topCategories' => $topCategories,
            'statusDistribution' => $statusDistribution,
            'latestAnalysis' => $latestAnalysis,
            'analyses' => $analyses,
            'analysisFilter' => $analysisFilter,
        ]);
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
            ->select([
                'students.id',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.middle_name',
                'students.suffix',
                'students.status',
            ])
            ->with([
                'latestEnrollment',
                'latestEnrollment.grade_section:id,school_year_id,grade_level,section',
            ])
            ->withCount([
                'reports as case_count' => fn ($query) =>
                    $query->whereBetween('reports.created_at', [$schoolYearStart, $schoolYearEnd])
            ])
            ->whereHas(
                'latestEnrollment',
                fn ($query) => $query->where('status', 'Enrolled')->whereNull('ended_at')
            )
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

        $relatedStats = (clone $relatedReports)
            ->selectRaw("
                COUNT(DISTINCT reports.id) AS academic_year_total,
                COUNT(DISTINCT reports.id) FILTER (
                    WHERE report_statuses.status_name NOT IN ('Resolved', 'Dismissed')
                ) AS ongoing_cases,
                COUNT(DISTINCT reports.id) FILTER (
                    WHERE report_statuses.status_name = 'Resolved'
                ) AS resolved_cases
            ")
            ->leftJoin('report_statuses', 'reports.current_status_id', '=', 'report_statuses.id')
            ->first();

        $academicYearTotal = (int) ($relatedStats->academic_year_total ?? 0);
        $ongoingCases = (int) ($relatedStats->ongoing_cases ?? 0);
        $resolvedCases = (int) ($relatedStats->resolved_cases ?? 0);

        $resolutionRate = $academicYearTotal > 0
            ? round(($resolvedCases / $academicYearTotal) * 100, 1)
            : 0;

        $submittedReports = Report::query()
            ->where('reported_by', $user->id)
            ->whereBetween('created_at', [$schoolYearStart, $schoolYearEnd])
            ->count();

        $statusDistribution = (clone $relatedReports)
            ->select('report_statuses.status_name', DB::raw('COUNT(DISTINCT reports.id) AS total'))
            ->join('report_statuses', 'reports.current_status_id', '=', 'report_statuses.id')
            ->groupBy('report_statuses.status_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item) => [
                'status_name' => $item->status_name,
                'total' => (int) $item->total,
                'percentage' => $academicYearTotal > 0 ? round(((int) $item->total / $academicYearTotal) * 100, 1) : 0,
            ])
            ->values();

        $recentCases = (clone $relatedReports)
            ->with([
                'category:id,category_name',
                'current_status:id,status_name',
                'students:id,first_name,last_name',
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
                    'student_name' => $student ? trim("{$student->first_name} {$student->last_name}") : 'Related student',
                    'category_name' => $report->category?->category_name ?? 'Incident',
                    'status_name' => $report->current_status?->status_name ?? 'Unknown',
                    'incident_date' => $report->incident_date?->format('Y-m-d'),
                    'incident_time' => $report->incident_time?->format('H:i'),
                    'created_at' => $report->created_at?->toIso8601String(),
                ];
            })
            ->values();

        $relatedStudentsView = $relatedStudents->map(fn ($student) => [
            'id' => $student->id,
            'student_number' => $student->student_number,
            'name' => trim("{$student->first_name} {$student->last_name}"),
            'grade_level' => $student->latestEnrollment?->grade_section?->grade_level,
            'section' => $student->latestEnrollment?->grade_section?->section,
            'status' => $student->status,
            'case_count' => (int) $student->case_count,
        ])->values();

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
