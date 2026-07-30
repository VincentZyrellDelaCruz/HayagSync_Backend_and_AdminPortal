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
        // === Weekly Analysis (Sunday 11:59 PM) ===
        $schedule->command('ai:generate weekly "' . now()->startOfWeek()->format('M d') . ' – ' . now()->endOfWeek()->format('M d, Y') . '"')
            ->weeklyOn(0, '23:59');

        // === Monthly Analysis (1st day of month, 12:05 AM) ===
        $schedule->command('ai:generate monthly "' . now()->subMonth()->format('F Y') . '"')
            ->monthlyOn(1, '00:05');

        // === Academic Year Analysis (March 31, 11:59 PM) ===
        $schedule->command('ai:generate yearly "A.Y. ' . now()->subYear()->format('Y') . '-' . now()->format('Y') . '"')
            ->yearlyOn(3, 31, '23:59');
    }
}
