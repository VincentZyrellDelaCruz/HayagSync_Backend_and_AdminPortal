<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysis;
use App\Models\Report;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
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
}
