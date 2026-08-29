<?php

namespace App\Jobs;

use App\Models\AiAnalysis;
use App\Models\Position;
use App\Models\Report;
use App\Models\SchoolYear;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateAiAnalysis implements ShouldQueue
{
    use Dispatchable, Queueable;

    public string $periodicity;
    public string $periodLabel;

    public function __construct(string $periodicity, string $periodLabel)
    {
        $this->periodicity = $periodicity;
        $this->periodLabel = $periodLabel;
    }

    public function handle(GeminiService $gemini): void
{
    $schoolYear = SchoolYear::latest()->first();
    Log::info("GenerateAiAnalysis started", [
        'periodicity' => $this->periodicity,
        'periodLabel' => $this->periodLabel,
        'schoolYear' => optional($schoolYear)->id,
    ]);

    $positions = Position::all();
    Log::info("Positions found", $positions->pluck('position_name')->toArray());

    $analyticsData = $this->buildAnalyticsData($this->periodicity, $this->periodLabel);
    Log::info("Analytics data built", ['length' => strlen($analyticsData), 'preview' => substr($analyticsData,0,200)]);

    foreach ($positions as $position) {
        $exists = AiAnalysis::where('periodicity', $this->periodicity)
            ->where('period_label', $this->periodLabel)
            ->where('school_year_id', optional($schoolYear)->id)
            ->where('position_id', $position->id)
            ->exists();

        Log::info("Checking position", [
            'position' => $position->position_name,
            'alreadyExists' => $exists
        ]);

        if ($exists) continue;

        try {
            $output = $gemini->generateUnifiedAnalysis(
                $this->periodicity,
                $this->periodLabel,
                $analyticsData,
                $position->position_name
            );

            Log::info("Gemini output received", [
                'position' => $position->position_name,
                'outputLength' => strlen($output),
                'outputPreview' => substr($output,0,200)
            ]);

            AiAnalysis::create([
                'id' => Str::uuid(),
                'periodicity' => $this->periodicity,
                'period_label' => $this->periodLabel,
                'output' => $output,
                'position_id' => $position->id,
                'school_year_id' => optional($schoolYear)->id,
            ]);

            Log::info("AiAnalysis created", ['position' => $position->position_name]);
        } catch (\Throwable $e) {
            Log::error("AI Analysis job failed for {$position->position_name}: ".$e->getMessage());
        }
    }
}


    private function buildAnalyticsData(string $periodicity, string $periodLabel): string
    {
        if ($periodicity === 'weekly') {
            $start = now()->startOfWeek();
            $end   = now()->endOfWeek();
        } elseif ($periodicity === 'monthly') {
            $start = now()->startOfMonth();
            $end   = now()->endOfMonth();
        } else { // yearly (academic year)
            $start = now()->month >= 6
                ? now()->startOfYear()->addMonths(5)
                : now()->subYear()->startOfYear()->addMonths(5);
            $end   = $start->copy()->addMonths(9)->endOfMonth();
        }

        $reports = Report::with(['category','current_status','students'])
            ->whereBetween('incident_date', [$start, $end])
            ->get();

        if ($reports->isEmpty()) {
            return "Incident Reports for {$periodLabel}: No reports found in this period.";
        }

        $analytics = "Incident Reports for {$periodLabel}:\n";
        foreach ($reports as $report) {
            $analytics .= "- {$report->incident_title} (" . optional($report->category)->name . ") "
                . "at {$report->location} on " . optional($report->incident_date)->format('M d, Y')
                . " | Status: " . optional($report->current_status)->status_name . "\n";
            $analytics .= "  Description: {$report->description}\n";
            if ($report->students->count()) {
                $analytics .= "  Students involved: " . $report->students->pluck('first_name')->join(', ') . "\n";
            }
        }

        return $analytics;
    }

}
