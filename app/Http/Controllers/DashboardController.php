<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        if ($user->isAdmin()) {
            $totalEmployees = User::where('role', 'karyawan')->where('status', 'active')->count();
            $todayPresent = Attendance::whereDate('date', $today)->whereNotNull('clock_in')->count();
            $pendingLeaves = LeaveRequest::where('status', 'pending')->where('current_approval_level', 3)->count();
            $pendingOvertimes = OvertimeRequest::where('status', 'pending')->where('current_approval_level', 3)->count();

            $recentAttendances = Attendance::with('user')
                ->whereDate('date', $today)
                ->latest()
                ->take(10)
                ->get();

            $recentLeaves = LeaveRequest::with('user')
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get();

            $recentOvertimes = OvertimeRequest::with('user')
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get();

            // Monthly attendance stats
            $monthlyStats = Attendance::whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return Inertia::render('Dashboard', [
                'role' => 'admin',
                'stats' => [
                    'totalEmployees' => $totalEmployees,
                    'todayPresent' => $todayPresent,
                    'pendingLeaves' => $pendingLeaves,
                    'pendingOvertimes' => $pendingOvertimes,
                    'monthlyStats' => $monthlyStats,
                ],
                'recentAttendances' => $recentAttendances,
                'recentLeaves' => $recentLeaves,
                'recentOvertimes' => $recentOvertimes,
            ]);
        }

        // Employee dashboard
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $monthAttendances = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();

        $pendingLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $pendingOvertimes = OvertimeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $jatahCuti = $user->jatah_cuti ?? 12;
        $approvedAnnualLeave = LeaveRequest::where('user_id', $user->id)
            ->where('type', 'cuti_tahunan')
            ->where('status', 'approved')
            ->whereYear('start_date', $currentYear)
            ->sum('total_days');
        $pendingAnnualLeave = LeaveRequest::where('user_id', $user->id)
            ->where('type', 'cuti_tahunan')
            ->where('status', 'pending')
            ->whereYear('start_date', $currentYear)
            ->sum('total_days');
        $remainingAnnualLeave = max(0, $jatahCuti - $approvedAnnualLeave - $pendingAnnualLeave);

        $latestPayroll = Payroll::where('user_id', $user->id)
            ->where('status', 'paid')
            ->latest('year')
            ->latest('month')
            ->first();

        return Inertia::render('Dashboard', [
            'role' => 'employee',
            'todayAttendance' => $todayAttendance,
            'monthAttendances' => $monthAttendances,
            'stats' => [
                'presentDays' => $monthAttendances->where('status', 'present')->count() + $monthAttendances->where('status', 'late')->count(),
                'lateDays' => $monthAttendances->where('status', 'late')->count(),
                'absentDays' => $monthAttendances->where('status', 'absent')->count(),
                'leaveDays' => $monthAttendances->where('status', 'leave')->count(),
                'pendingLeaves' => $pendingLeaves,
                'pendingOvertimes' => $pendingOvertimes,
                'remainingAnnualLeave' => $remainingAnnualLeave,
            ],
            'latestPayroll' => $latestPayroll,
        ]);
    }
}
