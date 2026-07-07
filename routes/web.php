<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'HAYAGSYNC API Backend',
        'status' => 'Online',
        'framework' => 'Laravel 13'
    ]);
});
