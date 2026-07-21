<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $weeklyReported = Report::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $weeklyResolved = Report::whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                                ->whereHas('current_status', fn($q) => $q->where('status_name', 'Resolved'))
                                ->count();
        $ongoingReports = Report::whereHas('current_status', fn($q) => $q->whereIn('status_name', ['Pending', 'Under Investigation']))->count();

        $topCategories = Report::select('incident_categories.category_name', DB::raw('count(*) as total'))
            ->join('incident_categories', 'reports.category_id', '=', 'incident_categories.id')
            ->groupBy('incident_categories.category_name')
            ->orderByDesc('total')
            ->take(5)
            ->pluck('total', 'incident_categories.category_name');

        $statusDistribution = Report::select('report_statuses.status_name', DB::raw('count(*) as total'))
            ->join('report_statuses', 'reports.current_status_id', '=', 'report_statuses.id')
            ->groupBy('report_statuses.status_name')
            ->pluck('total', 'report_statuses.status_name');

        $statusDistribution = $statusDistribution->map(fn($count) => round(($count / Report::count()) * 100));

        return view('dashboard', compact('weeklyReported', 'weeklyResolved', 'ongoingReports', 'topCategories', 'statusDistribution'));
    }
}
