<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Web\ActivityLogController;
use App\Http\Controllers\Web\ChatController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MeetingController;
use App\Http\Controllers\Web\OtpController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\StudentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
Route::get('/', function () {
    return view('welcome');
});
 */

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});


Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'staff_only'])->name('dashboard');


Route::prefix('/otp')->group(function () {
    Route::get('/', [OtpController::class, 'index'])->name('otp.select');
    Route::post('/', [OtpController::class, 'send'])->name('otp.send');
    Route::get('/verify', [OtpController::class, 'verifyForm'])->name('otp.verify');
    Route::post('/verify', [OtpController::class, 'verify'])->name('otp.verify.submit');
});


Route::middleware(['auth', 'staff_only'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::put('/meetings/{meeting}/{action}', [MeetingController::class, 'update'])->name('web.meetings.update');
    Route::post('/meetings/{meeting}/chat', [ChatController::class, 'store'])->name('web.chat_messages.store');

    Route::resource('/reports', ReportController::class)->names([
        'index'   => 'web.reports.index',
        'create'  => 'web.reports.create',
        'store'   => 'web.reports.store',
        'show'    => 'web.reports.show',
        'edit'    => 'web.reports.edit',
        'update'  => 'web.reports.update',
    ]);

    Route::get('/students', [StudentController::class, 'index'])->name('web.students.index');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('web.students.show');

    Route::get('/users', [UserController::class, 'index'])->name('web.users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('web.users.show');

    Route::get('/admin/activity_log', [ActivityLogController::class, 'index'])->name('web.activity_logs.index');

    /*
    Route::get('/staff/data', [UserController::class, 'getStaff'])->name('user.staff.data');
    Route::get('/parent/data', [UserController::class, 'getParentGuardian'])->name('user.parent.data');

    */
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
