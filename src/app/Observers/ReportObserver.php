<?php

namespace App\Observers;

use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReportObserver
{
    public function creating(Report $report): void
    {
        Log::info('ReportObserver creating() FIRED');

        $year = Carbon::now()->year;

        $lastCode = Report::whereYear('created_at', $year)
            ->orderBy('report_code', 'desc')
            ->value('report_code');

        if ($lastCode) {
            $number = (int) substr($lastCode, 9);
            $newNumber = str_pad($number + 1, 6, '0', STR_PAD_LEFT);
            $report->report_code = "BIR-{$year}-{$newNumber}";
        } else {
            $report->report_code = "BIR-{$year}-000001";
        }

        Log::info('Generated report code', [
            'report_code' => $report->report_code,
        ]);
    }
}
