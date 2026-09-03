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
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Inertia\Response;

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

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();

            if (in_array($status, [401, 403, 404, 419, 429, 500, 503])) {
                return $response
                    ->render('Fallback', [
                        'status' => $status,
                    ])
                    ->withSharedData();
            }

            return null;
        });
    }
}
