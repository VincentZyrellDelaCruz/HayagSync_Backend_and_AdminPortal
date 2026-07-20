<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Web\ReportController;
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


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'staff_only'])->name('dashboard');

Route::middleware(['auth', 'staff_only'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('/reports', ReportController::class)->names([
        'index'   => 'web.reports.index',
        'create'  => 'web.reports.create',
        'store'   => 'web.reports.store',
        'show'    => 'web.reports.show',
        'edit'    => 'web.reports.edit',
        'update'  => 'web.reports.update',
    ]);

    Route::get('/users', [UserController::class, 'index'])->name('web.users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('web.users.show');

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
