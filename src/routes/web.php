<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityCenterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Web\ActivityLogController;
use App\Http\Controllers\Web\ChatController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DataImportController;
use App\Http\Controllers\Web\MeetingController;
use App\Http\Controllers\Web\OtpController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\StudentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('auth/login');
})->name('home');

// ONE-TIME PASSWORD (OTP)
Route::prefix('/otp')->name('otp.')->controller(OtpController::class)->group(function () {
    Route::get('/', 'index')->name('select');
    Route::post('/', 'send')->name('send');
    Route::get('/verify', 'verifyForm')->name('verify');
    Route::post('/verify', 'verify')->name('verify.submit');
});

Route::middleware(['auth', 'portal_only'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['verified'])->name('dashboard');

    // BULLYING INCIDENT REPORT MANAGEMENT
    Route::prefix('/reports')->name('web.reports.')->controller(ReportController::class)->group(function () {
        Route::get('/', 'index')->name('index');

        Route::middleware('parent_only')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/student-search', 'studentSearch')->name('student-search');
        });

        Route::get('/evidence/{reportEvidence}/stream', 'streamEvidence')->name('evidence.stream');
        Route::get('/{id}', 'show')->name('show');

        Route::middleware('staff_only')->group(function () {
            Route::post('/{report}/forward', 'forward')->name('forward');
            Route::post('/{report}/resolve', 'resolve')->name('resolve');
            Route::post('/{report}/dismiss', 'dismiss')->name('dismiss');
            Route::put('/{id}', 'update')->name('update');
        });
    });

    // PROFILE CENTER
    Route::get('/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('settings.profile.destroy');

    // STUDENT DIRECTORY / RELATED STUDENTS
    Route::get('/students', [StudentController::class, 'index'])->name('web.students.index');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('web.students.show');

    // NOTIFICATION / INBOX HUB
    Route::prefix('/notifications')->name('notifications.')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::patch('/{inbox}/read', 'markRead')->name('read');
        Route::patch('/read-all', 'markAllRead')->name('read-all');
    });

    // STAFF-ONLY FEATURES
    Route::middleware('staff_only')->group(function () {

        // MEETINGS
        Route::put('/meetings/{meeting}/{action}', [MeetingController::class, 'update'])->name('web.meetings.update');
        Route::post('/meetings/{meeting}/chat', [ChatController::class, 'store'])->name('web.chat_messages.store');

        // USER DIRECTORY
        Route::get('/users', [UserController::class, 'index'])->name('web.users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('web.users.show');

        // ADMIN-ONLY ROUTES
        Route::middleware('admin_only')->prefix('/admin')->name('web.admin.')->group(function () {

            Route::get('/security', [SecurityCenterController::class, 'index'])->name('security.index');

            Route::patch('/security/events/{securityEvent}/resolve', [SecurityCenterController::class, 'resolve'])
                ->name('security.events.resolve');

            Route::prefix('/imports')->name('imports.')->controller(DataImportController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{batch}/status', 'status')->name('status');
            });
        });
    });
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
