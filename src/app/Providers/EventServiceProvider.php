<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Illuminate\Auth\Events\Login::class => [
            \App\Listeners\LogSuccessfulLogin::class,
            \App\Listeners\ClearFailedLoginCounter::class,
        ],
        \Illuminate\Auth\Events\Failed::class => [
            \App\Listeners\LogFailedLogin::class,
        ],
        \Illuminate\Auth\Events\Logout::class => [
            \App\Listeners\LogLogout::class,
        ],
    ];

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
    public function boot(): void
    {
        //
    }
}
