<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return \Inertia\Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');

    // Leave Requests
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/create', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');

    // Overtime Requests
    Route::get('/overtimes', [OvertimeController::class, 'index'])->name('overtimes.index');
    Route::get('/overtimes/create', [OvertimeController::class, 'create'])->name('overtimes.create');
    Route::post('/overtimes', [OvertimeController::class, 'store'])->name('overtimes.store');
    Route::post('/overtimes/{overtime}/approve', [OvertimeController::class, 'approve'])->name('overtimes.approve');
    Route::post('/overtimes/{overtime}/reject', [OvertimeController::class, 'reject'])->name('overtimes.reject');

    // Payroll
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::post('/payroll/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
    Route::post('/payroll/{payroll}/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.markPaid');
    Route::get('/payroll/{payroll}/export-pdf', [PayrollController::class, 'exportPdf'])->name('payroll.exportPdf');
    Route::get('/payroll/{payroll}/export-excel', [PayrollController::class, 'exportExcel'])->name('payroll.exportExcel');
    Route::get('/payroll-bulk-export', [PayrollController::class, 'bulkExportExcel'])->name('payroll.bulkExport');

    // Schedule Management (Jadwal Karyawan)
    Route::get('/schedule', [UserScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/schedule/{user}/monthly', [UserScheduleController::class, 'getMonthlySchedule'])->name('schedule.monthly');
    Route::put('/schedule/{user}/monthly', [UserScheduleController::class, 'updateMonthlySchedules'])->name('schedule.update');

    // Employee Management (Admin Only)
    Route::middleware('admin')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

        // Bulk Import
        Route::get('/employees/import', [BulkImportController::class, 'index'])->name('employees.import');
        Route::post('/bulk-import/preview', [BulkImportController::class, 'preview'])->name('bulk-import.preview');
        Route::post('/bulk-import/process', [BulkImportController::class, 'import'])->name('bulk-import.process');
        Route::get('/bulk-import/download-template', [BulkImportController::class, 'downloadTemplate'])->name('bulk-import.download');

        // Schedule Import
        Route::get('/schedule/import', [BulkImportController::class, 'scheduleImport'])->name('schedule.import');
        Route::post('/schedule/import/preview', [BulkImportController::class, 'schedulePreview'])->name('schedule.import.preview');
        Route::post('/schedule/import', [BulkImportController::class, 'scheduleStore'])->name('schedule.import.store');
        Route::get('/schedule/import/download-template', [BulkImportController::class, 'downloadScheduleTemplate'])->name('schedule.import.download');
    });
});

require __DIR__ . '/auth.php';
