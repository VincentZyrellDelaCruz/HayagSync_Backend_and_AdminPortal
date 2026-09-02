<?php

use App\Models\Inbox;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Inbox::whereNotNull('expires_at')
        ->where('expires_at', '<=', now())
        ->delete();
})->daily();
