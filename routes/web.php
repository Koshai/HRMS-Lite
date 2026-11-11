<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminSalaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeAdminController;
use App\Http\Controllers\ForcedPasswordChangeController;
use App\Http\Controllers\MyAttendanceController;
use App\Http\Controllers\MyProfileController;
use App\Http\Controllers\MySalaryController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('password/forced', [ForcedPasswordChangeController::class, 'edit'])
        ->name('password.forced.edit');
    Route::post('password/forced', [ForcedPasswordChangeController::class, 'update'])
        ->name('password.forced.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('my-profile', [MyProfileController::class, 'show'])->name('profile.mine');
        Route::get('my-profile/photo', [MyProfileController::class, 'photo'])->name('profile.mine.photo');

        Route::get('my-attendance', [MyAttendanceController::class, 'index'])->name('attendance.mine');
        Route::post('my-attendance/check-in', [MyAttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('my-attendance/check-out', [MyAttendanceController::class, 'checkOut'])->name('attendance.check-out');

        Route::get('my-salaries', [MySalaryController::class, 'index'])->name('salaries.mine');

        Route::get('attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/create', [AdminAttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [AdminAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance/{attendance}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
        Route::get('attendance/{attendance}/edit', [AdminAttendanceController::class, 'edit'])->name('attendance.edit');
        Route::put('attendance/{attendance}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

        Route::get('salaries', [AdminSalaryController::class, 'index'])->name('salaries.index');
        Route::get('salaries/create', [AdminSalaryController::class, 'create'])->name('salaries.create');
        Route::post('salaries', [AdminSalaryController::class, 'store'])->name('salaries.store');
        Route::get('salaries/{salary}', [AdminSalaryController::class, 'show'])->name('salaries.show');
        Route::get('salaries/{salary}/edit', [AdminSalaryController::class, 'edit'])->name('salaries.edit');
        Route::put('salaries/{salary}', [AdminSalaryController::class, 'update'])->name('salaries.update');
        Route::post('salaries/{salary}/mark-paid', [AdminSalaryController::class, 'markPaid'])->name('salaries.mark-paid');
        Route::post('salaries/{salary}/reverse-unpaid', [AdminSalaryController::class, 'reverseUnpaid'])->name('salaries.reverse-unpaid');

        Route::get('employees', [EmployeeAdminController::class, 'index'])->name('employees.index');
        Route::get('employees/create', [EmployeeAdminController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeAdminController::class, 'store'])->name('employees.store');
        Route::get('employees/{employee}', [EmployeeAdminController::class, 'show'])->name('employees.show');
        Route::get('employees/{employee}/edit', [EmployeeAdminController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeAdminController::class, 'update'])->name('employees.update');
        Route::post('employees/{employee}/deactivate', [EmployeeAdminController::class, 'deactivate'])->name('employees.deactivate');
        Route::post('employees/{employee}/reactivate', [EmployeeAdminController::class, 'reactivate'])->name('employees.reactivate');
        Route::post('employees/{employee}/reset-password', [EmployeeAdminController::class, 'resetPassword'])->name('employees.reset-password');
        Route::get('employees/{employee}/photo', [EmployeeAdminController::class, 'photo'])->name('employees.photo');
    });
});
