<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncidentController;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/logout', [AuthController::class, 'logout']);

    Route::apiResource('/incidents', IncidentController::class);
});

/* Testing API without token
Route::get('/students', function() {
    return Student::all();
});
 */
