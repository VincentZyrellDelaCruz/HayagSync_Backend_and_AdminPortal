<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAiAnalysis;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-ai-analysis-command')]
#[Description('Command description')]
class GenerateAiAnalysisCommand extends Command
{
    protected $signature = 'ai:generate {periodicity} {periodLabel}';
    protected $description = 'Dispatch AI analysis job for a given period';

    public function handle()
    {
        $periodicity = $this->argument('periodicity');
        $periodLabel = $this->argument('periodLabel');

        // Build analytics summary (you can refactor this into a service)
        // $summary = "Weekly: ... Monthly: ... Academic Year: ...";

        GenerateAiAnalysis::dispatch($periodicity, $periodLabel);

        $this->info("AI analysis job dispatched for {$periodicity} ({$periodLabel}).");
    }
}
