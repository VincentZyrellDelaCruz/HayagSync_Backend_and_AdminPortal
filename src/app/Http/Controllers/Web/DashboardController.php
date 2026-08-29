<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysis;
use App\Models\Report;
use App\Models\SchoolYear;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(GeminiService $gemini)
    {
        // Weekly
        $weeklyReported = Report::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $weeklyResolved = Report::whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                                ->whereHas('current_status', fn($q) => $q->where('status_name', 'Resolved'))
                                ->count();
        $ongoingReports = Report::whereHas('current_status', fn($q) => $q->whereIn('status_name', ['Pending', 'Under Investigation']))->count();

        // Monthly
        $monthlyReported = Report::whereYear('created_at', now()->year)
                                 ->whereMonth('created_at', now()->month)
                                 ->count();

        // Academic Year (example June–March)
        $schoolYearStart = now()->month >= 6
            ? now()->startOfYear()->addMonths(5)
            : now()->subYear()->startOfYear()->addMonths(5);
        $schoolYearEnd   = $schoolYearStart->copy()->addMonths(9)->endOfMonth();
        $yearlyReported  = Report::whereBetween('created_at', [$schoolYearStart, $schoolYearEnd])->count();

        // Top Categories
        $topCategories = Report::select('incident_categories.category_name', DB::raw('count(*) as total'))
            ->join('incident_categories', 'reports.category_id', '=', 'incident_categories.id')
            ->groupBy('incident_categories.category_name')
            ->orderByDesc('total')
            ->take(5)
            ->pluck('total', 'incident_categories.category_name');

        // Status Distribution
        $statusDistribution = Report::select('report_statuses.status_name', DB::raw('count(*) as total'))
            ->join('report_statuses', 'reports.current_status_id', '=', 'report_statuses.id')
            ->groupBy('report_statuses.status_name')
            ->pluck('total', 'report_statuses.status_name')
            ->map(fn($count) => round(($count / max(Report::count(), 1)) * 100));

        $latestAnalysis = AiAnalysis::orderByDesc('created_at')->first();
        $analyses = AiAnalysis::orderByDesc('created_at')->paginate(10);


        return Inertia::render('dashboard');
    }
}
