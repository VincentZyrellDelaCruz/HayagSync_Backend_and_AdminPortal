<?php

namespace App\Providers;

use App\Models\Meeting;
use App\Models\Report;
use App\Models\User;
use App\Observers\MeetingObserver;
use App\Observers\ReportObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Report::observe(ReportObserver::class);
        Meeting::observe(MeetingObserver::class);

        Gate::define('admin-only', function (User $user) {
            return $user->staff->is_admin === true;
        });
    }
}
