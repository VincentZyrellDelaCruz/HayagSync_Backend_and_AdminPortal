<?php

namespace App\Jobs;

use App\Models\AiGuidance;
use App\Models\Incident;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateAiParentingSupportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $incidentId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $gemini): void
    {
        try {

            $incident = Incident::with('category')->find($this->incidentId);

            if (!$incident) return;

            $tips_title = 'Parenting support tips for your child related to "' . $incident->incident_title . '"';

            $parenting_tips = $gemini->generateParentalSupport(
                $incident->category->category_name,
                $incident->incident_title,
                $incident->description
            );

            AiGuidance::updateOrCreate(
                [
                    'incident_id' => $incident->id,
                ],
                [
                    'tips_title' => $tips_title,
                    'generated_text' => $parenting_tips,
                ]
            );

        } catch (\Throwable $e) {

            Log::error('Gemini AI Parenting Support Failed', [
                'incident_id' => $this->incidentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
