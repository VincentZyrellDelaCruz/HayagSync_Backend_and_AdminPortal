<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAiAnalysis;
use Illuminate\Console\Command;

class GenerateAiAnalysisCommand extends Command
{
    protected $signature = 'ai:generate {periodicity} {periodLabel} {periodStart?} {periodEnd?}';
    protected $description = 'Dispatch AI analysis job for a given period';

    public function handle()
    {
        $periodicity = $this->argument('periodicity');
        $periodLabel = $this->argument('periodLabel');
        $periodStart = $this->argument('periodStart');
        $periodEnd = $this->argument('periodEnd');

        if (!in_array($periodicity, ['weekly', 'monthly', 'yearly'], true)) {
            $this->error('Invalid periodicity. Use weekly, monthly, or yearly.');

            return self::FAILURE;
        }

        GenerateAiAnalysis::dispatch($periodicity, $periodLabel, $periodStart, $periodEnd);

        $this->info("AI analysis job dispatched for {$periodicity} ({$periodLabel}).");

        return self::SUCCESS;
    }
}
