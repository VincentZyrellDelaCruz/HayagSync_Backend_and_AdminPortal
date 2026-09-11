<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\Log;

class ReportUrgencyService
{
    public function __construct(
        private readonly TraditionalUrgencyAnalyzer $traditionalAnalyzer,
        private readonly GeminiService $gemini,
    ) {}

    public function assess(Report $report): array
    {
        $traditionalResult = $this->traditionalAnalyzer->analyze($report);
        $dummyMode = (bool) config('gemini.dummy_mode', true);

        // USE TRADITIONAL ANALYSIS DURING TESTING TO AVOID UNNECESSARY AI USAGE
        if ($dummyMode) {
            Log::info('Report urgency assessed using traditional analyzer.', [
                'report_id' => $report->id,
                'urgent' => $traditionalResult['urgent'],
                'score' => $traditionalResult['score'],
                'critical_count' => count($traditionalResult['critical_signals']),
                'high_risk_count' => count($traditionalResult['high_risk_signals']),
                'context_count' => count($traditionalResult['context_signals']),
            ]);

            return [
                'urgent' => (bool) $traditionalResult['urgent'],
                'source' => 'traditional',
                'traditional_score' => $traditionalResult['score'],
                'traditional_signals' => $traditionalResult,
            ];
        }

        /*
         | NOTE:
         | Gemini is used only when dummy mode is disabled.
         | If Gemini is unavailable, fails, or returns an invalid response,
         | the traditional deterministic analyzer remains the fallback.
         */
        try {
            $geminiResult = $this->gemini->generateUrgencyAssessment(
                $report->incident_title,
                $report->description,
                $report->category?->category_name ?? 'Unknown',
                $report->location
            );

            if ($geminiResult !== null) {
                Log::info('Report urgency assessed using Gemini.', [
                    'report_id' => $report->id,
                    'urgent' => $geminiResult,
                    'traditional_score' => $traditionalResult['score'],
                ]);

                return [
                    'urgent' => $geminiResult,
                    'source' => 'gemini',
                    'traditional_score' => $traditionalResult['score'],
                    'traditional_signals' => $traditionalResult,
                ];
            }

            Log::warning('Gemini urgency assessment returned an invalid result. Falling back to traditional analyzer.', [
                'report_id' => $report->id,
                'traditional_result' => $traditionalResult['urgent'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gemini urgency assessment failed. Falling back to traditional analyzer.', [
                'report_id' => $report->id,
                'exception' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);
        }

        // FALLBACK: The traditional analysis remains available for deterministic decision
        return [
            'urgent' => (bool) $traditionalResult['urgent'],
            'source' => 'traditional_fallback',
            'traditional_score' => $traditionalResult['score'],
            'traditional_signals' => $traditionalResult,
        ];
    }
}
