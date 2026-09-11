<?php

use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ParentOnlyMiddleware;
use App\Http\Middleware\PortalOnlyMiddleware;
use App\Http\Middleware\StaffOnlyMIddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'portal_only' => PortalOnlyMiddleware::class,
            'parent_only' => ParentOnlyMiddleware::class,
            'staff_only' => StaffOnlyMIddleware::class,
            'admin_only' => AdminOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
