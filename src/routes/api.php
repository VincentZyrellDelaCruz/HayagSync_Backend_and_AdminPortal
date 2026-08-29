<?php

use App\Http\Controllers\Api\AiParentalSupportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InboxController;
use App\Http\Controllers\Api\IncidentCategoryController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PendingRegistrationController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\StudentParentGuardianController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/register/otp', [OtpController::class, 'generateOTP']);
Route::post('/register/otp/verify', [OtpController::class, 'validateOTP']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /* Route::get('/categories', [IncidentCategoryController::class, 'showAllCategories']);

    Route::apiResource('/incidents', IncidentController::class);

    Route::get('/parenting_tips', [AiParentalSupportController::class, 'index']);
    Route::get('/parenting_tips/{id}', [AiParentalSupportController::class, 'show']);

    Route::controller(StudentParentGuardianController::class)->group(function () {
        Route::get('/students', 'showAllStudent');
        Route::get('/parent/students', 'showAllRelatedStudent');
    });

    Route::get('/inbox', [InboxController::class, 'index']);
    Route::get('/inbox/{id}', [InboxController::class, 'show']);

    Route::get('/media/{path}', [MediaController::class, 'show']); */
});

/* Testing API without token
Route::get('/students', function() {
    return Student::all();
});
 */
