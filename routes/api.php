<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncidentCategoryController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\StudentParentGuardianController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [IncidentCategoryController::class, 'showAllCategories']);

    Route::apiResource('/incidents', IncidentController::class);

    Route::controller(StudentParentGuardianController::class)->group(function () {
        Route::get('/students', 'showAllStudent');
        Route::get('/parent/students', 'showAllRelatedStudent');
    });

    Route::get('/media/{path}', [MediaController::class, 'show']);
});

/* Testing API without token
Route::get('/students', function() {
    return Student::all();
});
 */
