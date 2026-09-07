<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(Schedule $schedule): void
    {
        // WEEKLY ANALYSIS (Every sunday at 11:59 PM)
        $weeklyStart = now()->startOfWeek();
        $weeklyEnd = now()->endOfWeek();

        $schedule->command(
            'ai:generate weekly "' .
                $weeklyStart->format('M d') .
                ' – ' .
                $weeklyEnd->format('M d, Y') .
                '" ' .
                $weeklyStart->toDateString() .
                ' ' .
                $weeklyEnd->toDateString()
        )->weeklyOn(0, '23:59');

        // MONTHLY ANALYSIS (Every 1st day of month at 12:05 AM)
        $monthlyStart = now()->subMonth()->startOfMonth();
        $monthlyEnd = $monthlyStart->copy()->endOfMonth();

        $schedule->command(
            'ai:generate monthly "' .
                $monthlyStart->format('F Y') .
                '" ' .
                $monthlyStart->toDateString() .
                ' ' .
                $monthlyEnd->toDateString()
        )->monthlyOn(1, '00:05');

        // ACADEMIC/SCHOOL YEAR ANALYSIS (Presume AI Analysis works on March 31 at 11:59 PM)
        $yearlyStart = now()->month >= 6
            ? now()->startOfYear()->addMonths(5)
            : now()->subYear()->startOfYear()->addMonths(5);

        $yearlyEnd = $yearlyStart->copy()->addMonths(9)->endOfMonth();

        $schedule->command(
            'ai:generate yearly "A.Y. ' .
                $yearlyStart->format('Y') .
                '-' .
                $yearlyEnd->format('Y') .
                '" ' .
                $yearlyStart->toDateString() .
                ' ' .
                $yearlyEnd->toDateString()
        )->yearlyOn(3, 31, '23:59');
    }
}
