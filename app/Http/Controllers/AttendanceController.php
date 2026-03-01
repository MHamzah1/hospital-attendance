<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        if ($user->isAdmin()) {
            $attendances = Attendance::with(['user', 'shift'])
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->orderBy('date', 'desc')
                ->orderBy('clock_in', 'desc')
                ->paginate(20);
        } else {
            $attendances = Attendance::with('shift')
                ->where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->orderBy('date', 'desc')
                ->paginate(20);
        }

        $todayAttendance = Attendance::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        return Inertia::render('Attendance/Index', [
            'attendances' => $attendances,
            'todayAttendance' => $todayAttendance,
            'filters' => ['month' => (int) $month, 'year' => (int) $year],
            'userShift' => $user->shift, // Pass user's default shift
        ]);
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            'photo' => 'required|string', // base64
        ]);

        $user = $request->user();
        $today = Carbon::today();
        $now = Carbon::now();

        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing && $existing->clock_in) {
            return back()->withErrors(['message' => 'Anda sudah melakukan clock in hari ini.']);
        }

        // Save photo
        $photoPath = $this->saveBase64Photo($request->photo, $user->id, 'in');

        // Get user's shift
        $shift = $user->shift;
        
        // Determine status based on shift
        if ($shift) {
            // Check if late based on shift's start time and tolerance
            $status = $shift->isLate($now->format('H:i:s')) ? 'late' : 'present';
        } else {
            // Fallback to default 08:00 if no shift assigned
            $status = $now->format('H:i') > '08:00' ? 'late' : 'present';
        }

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            [
                'shift_id' => $shift?->id,
                'clock_in' => $now->format('H:i:s'),
                'photo_in' => $photoPath,
                'status' => $status,
                'notes' => $request->notes,
            ]
        );

        return back()->with('success', 'Clock In berhasil!');
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
        ]);

        $user = $request->user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return back()->withErrors(['message' => 'Anda belum melakukan clock in hari ini.']);
        }

        if ($attendance->clock_out) {
            return back()->withErrors(['message' => 'Anda sudah melakukan clock out hari ini.']);
        }

        $photoPath = $this->saveBase64Photo($request->photo, $user->id, 'out');

        $attendance->update([
            'clock_out' => $now->format('H:i:s'),
            'photo_out' => $photoPath,
        ]);

        return back()->with('success', 'Clock Out berhasil!');
    }

    private function saveBase64Photo(string $base64, int $userId, string $type): string
    {
        $image = str_replace('data:image/jpeg;base64,', '', $base64);
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);

        $fileName = "attendance/{$userId}_{$type}_" . Carbon::now()->format('Ymd_His') . '.jpg';

        Storage::disk('public')->put($fileName, base64_decode($image));

        return $fileName;
    }
}
