<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityCenterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Web\ActivityLogController;
use App\Http\Controllers\Web\ChatController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MeetingController;
use App\Http\Controllers\Web\OtpController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\StudentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return Inertia::render('dashboard');
    }
    return Inertia::render('auth/login');
})->name('home');;

Route::prefix('/otp')->group(function () {
    Route::get('/', [OtpController::class, 'index'])->name('otp.select');
    Route::post('/', [OtpController::class, 'send'])->name('otp.send');
    Route::get('/verify', [OtpController::class, 'verifyForm'])->name('otp.verify');
    Route::post('/verify', [OtpController::class, 'verify'])->name('otp.verify.submit');
});;


Route::middleware(['auth', 'staff_only'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['verified'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('settings.profile.destroy');

    Route::put('/meetings/{meeting}/{action}', [MeetingController::class, 'update'])->name('web.meetings.update');
    Route::post('/meetings/{meeting}/chat', [ChatController::class, 'store'])->name('web.chat_messages.store');

    Route::prefix('/reports')->name('web.reports.')->controller(ReportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/reports/evidence/{reportEvidence}/stream', 'streamEvidence')->name('evidence.stream');
        Route::post('/{report}/forward', 'forward')->name('forward');
        Route::post('/{report}/resolve', 'resolve')->name('resolve');
        Route::post('/{report}/dismiss', 'dismiss')->name('dismiss');
        Route::put('/{id}', 'update')->name('update');
    });

    /* Route::resource('/reports', ReportController::class)->names([
        'index'   => 'web.reports.index',
        'create'  => 'web.reports.create',
        'store'   => 'web.reports.store',
        'show'    => 'web.reports.show',
        'edit'    => 'web.reports.edit',
        'update'  => 'web.reports.update',
    ]); */

    Route::get('/students', [StudentController::class, 'index'])->name('web.students.index');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('web.students.show');

    Route::get('/users', [UserController::class, 'index'])->name('web.users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('web.users.show');

    /* Route::get('/admin/activity_log', [ActivityLogController::class, 'index'])->middleware(['can:admin-only'])
        ->name('web.activity_logs.index'); */

    Route::middleware('admin_only')->prefix('admin')->name('web.admin.')->group(function () {
        Route::get('/security', [SecurityCenterController::class, 'index'])->name('security.index');
        Route::patch('/security/events/{securityEvent}/resolve', [SecurityCenterController::class, 'resolve'])
            ->name('security.events.resolve');
    });

    /* Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard'); */
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
