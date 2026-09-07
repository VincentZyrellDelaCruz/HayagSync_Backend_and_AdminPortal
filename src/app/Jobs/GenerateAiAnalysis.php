<?php

namespace App\Jobs;

use App\Models\AiAnalysis;
use App\Models\Position;
use App\Models\Report;
use App\Models\SchoolYear;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateAiAnalysis implements ShouldQueue
{
    use Dispatchable, Queueable;

    public string $periodicity;
    public string $periodLabel;
    public ?string $periodStart;
    public ?string $periodEnd;

    public function __construct(string $periodicity, string $periodLabel, ?string $periodStart = null,
        ?string $periodEnd = null)
    {
        $this->periodicity = $periodicity;
        $this->periodLabel = $periodLabel;
        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;
    }

    public function handle(GeminiService $gemini): void
    {
        $schoolYear = SchoolYear::where('is_active', true)->latest()->first();

        $positions = Position::query()->select(['id','position_name'])->get();

        $analyticsData = $this->buildAnalyticsData($this->periodicity, $this->periodLabel);

        if ($analyticsData === null) return;

        foreach ($positions as $position) {
            $lockKey = sprintf(
                'ai-analysis:%s:%s:%s:%s',
                $this->periodicity,
                $this->periodLabel,
                $position->id,
                optional($schoolYear)->id ?? 'none'
            );

            $lock = Cache::lock($lockKey, 300);

            if (!$lock->get()) continue;

            try {
                $existing = AiAnalysis::where('periodicity',$this->periodicity)
                    ->where('period_label',$this->periodLabel)
                    ->where('school_year_id', optional($schoolYear)->id)
                    ->where('position_id', $position->id)
                    ->first();

                // Generates temporary fake AI response to avoid token wastage
                $dummyMode = (bool) config('gemini.dummy_mode', true);

                if ($existing &&
                    ($dummyMode || !Str::startsWith($existing->output, '[SIMULATED AI ANALYSIS]'))
                ) { continue; }

                try {
                    if ($dummyMode) {
                        $output = $gemini->generateDummyAnalysis(
                            $this->periodicity,
                            $this->periodLabel,
                            $analyticsData,
                            $position->position_name
                        );
                    }
                    else {
                        continue;
                    }

                    if ($existing) {
                        $existing->update([
                            'output' => $output,
                        ]);
                    }
                    else {
                        AiAnalysis::create([
                            'id' => Str::uuid(),
                            'periodicity' => $this->periodicity,
                            'period_label' => $this->periodLabel,
                            'output' => $output,
                            'position_id' => $position->id,
                            'school_year_id' => optional($schoolYear)->id,
                        ]);
                    }
                } catch (\Throwable $e) {
                    throw $e;
                }
            } finally {
                $lock->release();
            }
        }
    }

    private function buildAnalyticsData(string $periodicity, string $periodLabel): ?string
    {
        [$start, $end] = $this->resolvePeriodDates($periodicity);

        if ($periodicity === 'weekly') {
            $previousStart = $start->copy()->subWeek()->startOfWeek();
            $previousEnd = $start->copy()->subWeek()->endOfWeek();
        }
        elseif ($periodicity === 'monthly') {
            $previousStart = $start->copy()->subMonth()->startOfMonth();
            $previousEnd = $start->copy()->subMonth()->endOfMonth();
        }
        else {
            $previousStart = $start->copy()->subYear()->startOfYear();
            $previousEnd = $start->copy()->subYear()->endOfYear();
        }

        $resolvedStatusId = DB::table('report_statuses')->where('status_name', 'Resolved')->value('id');

        $baseQuery = Report::query()
            ->whereHas('report_updates', function ($query) use ($resolvedStatusId, $start, $end) {
                $query->where('status_id', $resolvedStatusId)
                    ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);
            });

        $totalReports = (clone $baseQuery)->count();

        $minimumReports = max(1, (int) config('gemini.minimum_reports', 2));

        if ($totalReports < $minimumReports) return null;

        $previousTotal = Report::query()
            ->whereHas('report_updates', function ($query) use ($resolvedStatusId, $previousStart, $previousEnd) {
                $query->where('status_id', $resolvedStatusId)
                    ->whereBetween('created_at', [
                        $previousStart->startOfDay(),
                        $previousEnd->endOfDay(),
                    ]);
            })->count();

        $percentageChange = $previousTotal === 0 ? 100
            : round((($totalReports - $previousTotal) / $previousTotal) * 100, 1);

        $categoryCounts = (clone $baseQuery)->join(
                'incident_categories',
                'reports.category_id',
                '=',
                'incident_categories.id'
            )
            ->select(
                'incident_categories.category_name',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(
                'incident_categories.category_name'
            )->orderByDesc('total')->limit(5)->get();

        $locationRows = (clone $baseQuery)->whereNotNull('location')
            ->where('location', '<>', '')
            ->select(
                'location',
                DB::raw('COUNT(*) as total')
            )->groupBy('location')->orderByDesc('total')->limit(20)->get();

        $locationCounts = [];

        foreach ($locationRows as $locationRow) {
            $location = $this->normalizeLocation($locationRow->location);

            if (!isset($locationCounts[$location])) {
                $locationCounts[$location] = 0;
            }

            $locationCounts[$location] += (int) $locationRow->total;
        }

        arsort($locationCounts);

        $locationCounts = array_slice($locationCounts, 0, 5, true);

        if ($periodicity === 'weekly') {
            $trend = (clone $baseQuery)->select(
                    'incident_date',
                    DB::raw('COUNT(*) as total')
                )->groupBy('incident_date')->orderBy('incident_date')->get();
        }
        else {
            $trend = (clone $baseQuery)->selectRaw(
                    "DATE_TRUNC('month', incident_date) as period, COUNT(*) as total"
                )->groupByRaw(
                    "DATE_TRUNC('month', incident_date)"
                )->orderByRaw(
                    "DATE_TRUNC('month', incident_date)"
                )->get();
        }

        $caseLimit = max(1, (int) config('gemini.max_case_context', 20));

        $cases = (clone $baseQuery) ->join(
                'incident_categories',
                'reports.category_id',
                '=',
                'incident_categories.id'
            )
            ->select(
                'reports.id',
                'reports.incident_date',
                'reports.location',
                'incident_categories.category_name'
            )
            ->orderByDesc('reports.incident_date')
            ->limit($caseLimit)
            ->get();

        $reportIds = $cases->pluck('id');

        $involvementCounts = DB::table('report_student')
            ->whereIn('report_id', $reportIds)
            ->select(
                'report_id',
                'involvement_type',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(
                'report_id',
                'involvement_type'
            )
            ->get()
            ->groupBy('report_id');

        $disciplinaryCounts = DB::table('disciplinary_actions')
            ->whereIn('report_id', $reportIds)
            ->select(
                'report_id',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('report_id')
            ->pluck('total', 'report_id');

        $analytics = "Period: {$periodLabel}\n";
        $analytics .= "Periodicity: {$periodicity}\n";
        $analytics .= "Analysis basis: Resolved incident reports only\n";
        $analytics .= "Resolved reports in period: {$totalReports}\n";
        $analytics .= "Previous comparable period resolved reports: {$previousTotal}\n";
        $analytics .= "Change from previous comparable period: {$percentageChange}%\n";

        $analytics .= "Top incident categories:\n";

        foreach ($categoryCounts as $item) {
            $analytics .= "- {$item->category_name}: {$item->total}\n";
        }

        if (!empty($locationCounts)) {
            $analytics .= "Top location groups:\n";

            foreach ($locationCounts as $location => $total) {
                $analytics .= "- {$location}: {$total}\n";
            }
        }

        $analytics .= "Incident trend:\n";

        foreach ($trend as $item) {
            $period = $item->period ?? $item->incident_date;

            if ($period instanceof \DateTimeInterface) {
                $period = $periodicity === 'weekly'
                    ? $period->format('M d, Y')
                    : $period->format('M Y');
            }

            $analytics .= "- {$period}: {$item->total}\n";
        }

        $analytics .= "Anonymized resolved case context:\n";

        foreach ($cases as $index => $case) {
            $caseNumber = $index + 1;

            $roleCounts = collect(
                $involvementCounts->get($case->id, collect())
            )->mapWithKeys(function ($item) {
                $role = strtolower(
                    trim((string) $item->involvement_type)
                );

                $role = match ($role) {
                    'victim', 'target' => 'affected_student',
                    'offender' => 'offender_recorded',
                    'witness' => 'witness_recorded',
                    default => 'other_recorded',
                };

                return [
                    $role => (int) $item->total,
                ];
            });

            $analytics .= "- Case {$caseNumber}: ";
            $analytics .= "Date {$case->incident_date}; ";
            $analytics .= "Category {$case->category_name}; ";
            $analytics .= "Location group " .
                $this->normalizeLocation($case->location) . "; ";
            $analytics .= "Affected students recorded " .
                ($roleCounts['affected_student'] ?? 0) . "; ";
            $analytics .= "Offender records " .
                ($roleCounts['offender_recorded'] ?? 0) . "; ";
            $analytics .= "Witness records " .
                ($roleCounts['witness_recorded'] ?? 0) . "; ";
            $analytics .= "Disciplinary action recorded " .
                ($disciplinaryCounts[$case->id] ?? 0 > 0 ? 'Yes' : 'No') . "\n";
        }

        return $analytics;
    }

    private function normalizeLocation(?string $location): string
    {
        $location = Str::lower(Str::squish((string) $location));

        if ($location === '') return 'Unspecified';

        if (Str::contains($location, ['classroom', 'room', 'laboratory', 'lab'])) {
            return 'Classroom / Learning Area';
        }

        if (Str::contains($location, ['hallway', 'corridor', 'stair', 'walkway'])) {
            return 'Hallway / Corridor';
        }

        if (Str::contains($location, ['canteen', 'cafeteria'])) {
            return 'Canteen / Cafeteria';
        }

        if (Str::contains($location, ['comfort room', 'restroom', 'toilet', 'bathroom'])) {
            return 'Restroom';
        }

        if (Str::contains($location, ['gym', 'gymnasium', 'court'])) {
            return 'Sports / Activity Area';
        }

        if (Str::contains($location, ['online', 'internet', 'social media', 'messenger'])) {
            return 'Digital / Online';
        }

        if (Str::contains($location, ['library'])) {
            return 'Library';
        }

        return 'Other';
    }

    private function resolvePeriodDates(string $periodicity): array
    {
        if ($this->periodStart && $this->periodEnd) {
            return [
                Carbon::parse($this->periodStart)->startOfDay(),
                Carbon::parse($this->periodEnd)->endOfDay(),
            ];
        }

        if ($periodicity === 'weekly') {
            $start = now()->startOfWeek();
            $end = now()->endOfWeek();
        }
        elseif ($periodicity === 'monthly') {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
        }
        else {
            $start = now()->month >= 6
                ? now()->startOfYear()->addMonths(5)
                : now()->subYear()->startOfYear()->addMonths(5);

            $end = $start->copy()->addMonths(9)->endOfMonth();
        }

        return [$start, $end];
    }
}
