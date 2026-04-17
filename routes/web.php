<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollImportController;
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
    Route::get('/attendance/export-excel', [AttendanceController::class, 'exportExcel'])->name('attendance.exportExcel');
    Route::get('/attendance/export-pdf', [AttendanceController::class, 'exportPdf'])->name('attendance.exportPdf');

    // Leave Requests
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/export-excel', [LeaveController::class, 'exportExcel'])->name('leaves.exportExcel');
    Route::get('/leaves/export-pdf', [LeaveController::class, 'exportPdf'])->name('leaves.exportPdf');
    Route::get('/leaves/create', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');

    // Overtime Requests
    Route::get('/overtimes', [OvertimeController::class, 'index'])->name('overtimes.index');
    Route::get('/overtimes/export-excel', [OvertimeController::class, 'exportExcel'])->name('overtimes.exportExcel');
    Route::get('/overtimes/export-pdf', [OvertimeController::class, 'exportPdf'])->name('overtimes.exportPdf');
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
    Route::get('/payroll/{payroll}/export-react-pdf', [PayrollController::class, 'exportReactPdf'])->name('payroll.exportReactPdf');
    Route::get('/payroll/{payroll}/export-excel', [PayrollController::class, 'exportExcel'])->name('payroll.exportExcel');
    Route::get('/payroll-bulk-export', [PayrollController::class, 'bulkExportExcel'])->name('payroll.bulkExport');

    // Payroll Import
    Route::get('/payroll-import', [PayrollImportController::class, 'index'])->name('payroll.import');
    Route::post('/payroll-import/preview', [PayrollImportController::class, 'preview'])->name('payroll.import.preview');
    Route::post('/payroll-import/process', [PayrollImportController::class, 'import'])->name('payroll.import.process');
    Route::get('/payroll-import/template', [PayrollImportController::class, 'downloadTemplate'])->name('payroll.import.template');

    // Schedule Management (Jadwal Karyawan)
    Route::get('/schedule', [UserScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/schedule/{user}/monthly', [UserScheduleController::class, 'getMonthlySchedule'])->name('schedule.monthly');
    Route::put('/schedule/{user}/monthly', [UserScheduleController::class, 'updateMonthlySchedules'])->name('schedule.update');

    // Employee Management (Admin Only)
    Route::middleware('admin')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        
        // Specific routes BEFORE parameter routes
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::get('/employees/import', [BulkImportController::class, 'index'])->name('employees.import');
        Route::get('/bulk-import/download-template', [BulkImportController::class, 'downloadTemplate'])->name('bulk-import.download');
        
        // Bulk Import routes
        Route::post('/bulk-import/preview', [BulkImportController::class, 'preview'])->name('bulk-import.preview');
        Route::post('/bulk-import/process', [BulkImportController::class, 'import'])->name('bulk-import.process');
        
        // Parameter routes AFTER specific routes
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

        // Schedule Import
        Route::get('/schedule/import', [BulkImportController::class, 'scheduleImport'])->name('schedule.import');
        Route::post('/schedule/import/preview', [BulkImportController::class, 'schedulePreview'])->name('schedule.import.preview');
        Route::post('/schedule/import', [BulkImportController::class, 'scheduleStore'])->name('schedule.import.store');
        Route::get('/schedule/import/download-template', [BulkImportController::class, 'downloadScheduleTemplate'])->name('schedule.import.download');

        // Schedule Management API (for React UI with bulk import/paste)
        Route::get('/schedules/{month}/{year}', [UserScheduleController::class, 'listAllSchedules'])->name('schedules.list');
        Route::post('/schedules/bulk', [UserScheduleController::class, 'bulkUpdateSchedules'])->name('schedules.bulk-update');
        Route::get('/schedules/{user}/history', [UserScheduleController::class, 'getHistory'])->name('schedules.history');

        // Department & Unit Management
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments/sync-from-excel', [DepartmentController::class, 'syncFromExcel'])->name('departments.syncFromExcel');
        Route::post('/departments', [DepartmentController::class, 'storeDepartment'])->name('departments.store');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::post('/departments/units', [DepartmentController::class, 'storeUnit'])->name('departments.units.store');
        Route::delete('/departments/units/{unit}', [DepartmentController::class, 'destroyUnit'])->name('departments.units.destroy');
        Route::put('/departments/units/{unit}/manager', [DepartmentController::class, 'updateManager'])->name('departments.updateManager');
        Route::post('/departments/job-positions', [DepartmentController::class, 'storeJobPosition'])->name('departments.jobPositions.store');
        Route::delete('/departments/job-positions/{jobPosition}', [DepartmentController::class, 'destroyJobPosition'])->name('departments.jobPositions.destroy');
    });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/mobile.php';
