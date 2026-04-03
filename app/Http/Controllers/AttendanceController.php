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
        $user   = $request->user();
        $month  = $request->get('month', Carbon::now()->month);
        $year   = $request->get('year', Carbon::now()->year);
        $search = $request->get('search', '');
        $status = $request->get('status', '');

        if ($user->isAdmin()) {
            $query = Attendance::with(['user', 'shift'])
                ->whereMonth('date', $month)
                ->whereYear('date', $year);

            // Filter pencarian nama karyawan
            if ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }

            // Filter status
            if ($status && in_array($status, ['present', 'late', 'absent', 'sick', 'leave'])) {
                $query->where('status', $status);
            }

            $attendances = $query
                ->orderBy('date', 'desc')
                ->orderBy('clock_in', 'desc')
                ->paginate(20)
                ->through(fn ($a) => $this->appendPhotoUrls($a));
        } else {
            $query = Attendance::with(['shift'])
                ->where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year);

            if ($status && in_array($status, ['present', 'late', 'absent', 'sick', 'leave'])) {
                $query->where('status', $status);
            }

            $attendances = $query
                ->orderBy('date', 'desc')
                ->paginate(20)
                ->through(fn ($a) => $this->appendPhotoUrls($a));
        }

        $todayAttendance = Attendance::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($todayAttendance) {
            $todayAttendance = $this->appendPhotoUrls($todayAttendance);
        }

        // Get today's schedule for the current user
        $todaySchedule = null;
        if (!$user->isAdmin()) {
            $todaySchedule = \App\Models\UserSchedule::with('shift')
                ->where('user_id', $user->id)
                ->whereDate('date', Carbon::today())
                ->first();
        }

        return Inertia::render('Attendance/Index', [
            'attendances'    => $attendances,
            'todayAttendance'=> $todayAttendance,
            'todaySchedule'  => $todaySchedule,
            'filters'        => [
                'month'  => (int) $month,
                'year'   => (int) $year,
                'search' => $search,
                'status' => $status,
            ],
            'userShift' => $user->shift,
        ]);
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            'photo' => 'required|string', // base64
        ]);

        $user  = $request->user();
        $today = Carbon::today();
        $now   = Carbon::now();

        // Check if user has "Libur" (Leave) schedule for today
        $scheduleToday = \App\Models\UserSchedule::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($scheduleToday && $scheduleToday->shift && strtolower($scheduleToday->shift->name) === 'libur') {
            return back()->withErrors(['message' => 'Tidak Ada Jadwal Kerja Karena Anda Sedang Libur']);
        }

        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // Cek apakah sudah clock in (bukan hanya existing record)
        if ($existing?->clock_in) {
            return back()->withErrors(['message' => 'Anda sudah melakukan clock in hari ini.']);
        }

        // Simpan foto
        $photoPath = $this->saveBase64Photo($request->photo, $user->id, 'in');

        // Ambil shift karyawan
        $shift = $user->shift;

        // Tentukan status berdasarkan shift
        if ($shift) {
            $status = $shift->isLate($now->format('H:i:s')) ? 'late' : 'present';
        } else {
            // Fallback default jam 08:00 jika tidak ada shift
            $status = $now->format('H:i:s') > '08:00:00' ? 'late' : 'present';
        }

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            [
                'shift_id'  => $shift?->id,
                'clock_in'  => $now->format('H:i:s'),
                'photo_in'  => $photoPath,
                'status'    => $status,
                'notes'     => $request->notes,
            ]
        );

        return back()->with('success', 'Clock In berhasil!');
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
        ]);

        $user  = $request->user();
        $today = Carbon::today();
        $now   = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance?->clock_in) {
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

    /**
     * Tambahkan URL publik foto clock in/out dan kalkulasi keterlambatan
     * ke setiap record absensi sebelum dikirim ke frontend.
     */
    private function appendPhotoUrls(Attendance $attendance): Attendance
    {
        // URL foto clock in
        $attendance->photo_in_url = $attendance->photo_in
            ? Storage::disk('public')->url($attendance->photo_in)
            : null;

        // URL foto clock out
        $attendance->photo_out_url = $attendance->photo_out
            ? Storage::disk('public')->url($attendance->photo_out)
            : null;

        // Kalkulasi durasi keterlambatan (jam, menit, detik)
        $attendance->late_duration = $this->calculateLateDuration($attendance);

        return $attendance;
    }

    /**
     * Hitung durasi keterlambatan dalam format "Xj Ym Zd".
     * Mengembalikan null jika tidak terlambat atau data tidak tersedia.
     */
    private function calculateLateDuration(Attendance $attendance): ?string
    {
        if ($attendance->status !== 'late' || !$attendance->clock_in || !$attendance->shift) {
            return null;
        }

        $shiftStart = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->shift->start_time);
        $clockIn    = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->clock_in);

        $diffSeconds = $clockIn->diffInSeconds($shiftStart, false);

        // Jika clock in lebih awal atau sama dengan shift start, tidak terlambat
        if ($diffSeconds <= 0) {
            return null;
        }

        $hours   = (int) floor($diffSeconds / 3600);
        $minutes = (int) floor(($diffSeconds % 3600) / 60);
        $seconds = (int) ($diffSeconds % 60);

        $parts = [];
        if ($hours > 0)   $parts[] = "{$hours}j";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($seconds > 0 || empty($parts)) $parts[] = "{$seconds}d";

        return implode(' ', $parts);
    }

    /**
     * Simpan foto base64 ke storage dan kembalikan path-nya.
     * Mendukung format JPEG, PNG, dan WebP.
     */
    private function saveBase64Photo(string $base64, int $userId, string $type): string
    {
        // Deteksi format dan strip data URI prefix
        $extension = 'jpg';
        if (str_contains($base64, 'data:image/png')) {
            $extension = 'png';
        } elseif (str_contains($base64, 'data:image/webp')) {
            $extension = 'webp';
        }

        $image = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $image = str_replace(' ', '+', $image);

        $fileName = "attendance/{$userId}_{$type}_" . Carbon::now()->format('Ymd_His') . ".{$extension}";

        Storage::disk('public')->put($fileName, base64_decode($image));

        return $fileName;
    }
}
