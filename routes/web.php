<?php

use App\Http\Controllers\Incidents\IncidentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Users\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'staff_only'])->name('dashboard');

Route::middleware(['auth', 'staff_only'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('/incidents', IncidentController::class)->names([
        'index'   => 'web.incidents.index',
        'create'  => 'web.incidents.create',
        'store'   => 'web.incidents.store',
        'show'    => 'web.incidents.show',
        'edit'    => 'web.incidents.edit',
        'update'  => 'web.incidents.update',
        'destroy' => 'web.incidents.destroy',
    ]);

    Route::prefix('/users')->group(function () {
        Route::get('staffs/data', [StaffController::class, 'getStaff'])->name('staff.data');
        Route::resource('/staffs', StaffController::class)->names([
            'index'   => 'web.staffs.index',
            'create'  => 'web.staffs.create',
            'store'   => 'web.staffs.store',
            'show'    => 'web.staffs.show',
            'edit'    => 'web.staffs.edit',
            'update'  => 'web.staffs.update',
            'destroy' => 'web.staffs.destroy',
        ]);

        Route::get('parent_guardians/data', [IncidentController::class, 'getParentGuardians'])->name('parent_guardians.data');
        Route::resource('/parent_guardians', IncidentController::class)->names([
            'index'   => 'web.parent_guardians.index',
            'create'  => 'web.parent_guardians.create',
            'store'   => 'web.parent_guardians.store',
            'show'    => 'web.parent_guardians.show',
            'edit'    => 'web.parent_guardians.edit',
            'update'  => 'web.parent_guardians.update',
            'destroy' => 'web.parent_guardians.destroy',
        ]);
    });
});

require __DIR__.'/auth.php';
