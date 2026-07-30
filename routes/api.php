<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TimelineController;
use Illuminate\Support\Facades\Route;

// Public Authentication
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register-parent', [AuthController::class, 'registerParent']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth profile actions
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/update-avatar', [AuthController::class, 'updateAvatar']);
    Route::post('/upload', [ReportController::class, 'uploadFile']);

    // Reports lifecycle
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/{id}', [ReportController::class, 'show']);
    Route::post('/reports/{id}/escalate', [ReportController::class, 'escalate']);
    Route::post('/reports/{id}/resolve', [ReportController::class, 'resolve']);
    Route::post('/reports/{id}/appeal', [ReportController::class, 'appeal']);
    Route::post('/reports/{id}/re-appeal', [ReportController::class, 'reAppeal']);
    Route::post('/reports/{id}/ai-summary', [ReportController::class, 'updateAiSummary']);

    // Meetings scheduler
    Route::get('/reports/{reportId}/meetings', [MeetingController::class, 'getMeetingsForReport']);
    Route::post('/meetings', [MeetingController::class, 'store']);
    Route::put('/meetings/{id}', [MeetingController::class, 'reschedule']);

    // Coordination Chat
    Route::get('/meetings/{meetingId}/chat', [ChatController::class, 'getChatMessages']);
    Route::post('/chat', [ChatController::class, 'store']);
    Route::post('/meetings/{meetingId}/chat/read', [ChatController::class, 'markAsRead']);

    // Audit logs timeline
    Route::get('/reports/{reportId}/timeline', [TimelineController::class, 'getTimelineEvents']);
    Route::post('/reports/{reportId}/timeline', [TimelineController::class, 'store']);

    // Parent directories
    Route::get('/directory/parents', [DirectoryController::class, 'getParents']);
});

/* Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */
