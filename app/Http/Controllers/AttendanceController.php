<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user      = $request->user();
        $dateFrom  = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo    = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $search    = $request->get('search', '');
        $status    = $request->get('status', '');
        $unitFilter = $request->get('unit', 'all');
        $deptFilter = $request->get('department', 'all');

        if ($user->isAdmin()) {
            $query = Attendance::with(['user.departmentModel', 'user.unitModel', 'shift'])
                ->whereBetween('date', [$dateFrom, $dateTo]);

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

            // Filter unit
            if ($unitFilter !== 'all') {
                $query->whereHas('user', function ($q) use ($unitFilter) {
                    $q->where('unit_id', $unitFilter);
                });
            }

            // Filter department
            if ($deptFilter !== 'all') {
                $query->whereHas('user', function ($q) use ($deptFilter) {
                    $q->where('department_id', $deptFilter);
                });
            }

            $attendances = $query
                ->orderBy('date', 'desc')
                ->orderBy('clock_in', 'desc')
                ->paginate(25)
                ->through(fn ($a) => $this->appendPhotoUrls($a));
        } else {
            $query = Attendance::with(['shift'])
                ->where('user_id', $user->id)
                ->whereBetween('date', [$dateFrom, $dateTo]);

            if ($status && in_array($status, ['present', 'late', 'absent', 'sick', 'leave'])) {
                $query->where('status', $status);
            }

            $attendances = $query
                ->orderBy('date', 'desc')
                ->paginate(25)
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

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $units = Unit::orderBy('name')->get(['id', 'name', 'department_id']);

        return Inertia::render('Attendance/Index', [
            'attendances'    => $attendances,
            'todayAttendance'=> $todayAttendance,
            'todaySchedule'  => $todaySchedule,
            'filters'        => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'search'    => $search,
                'status'    => $status,
                'unit'      => $unitFilter,
                'department'=> $deptFilter,
            ],
            'departments' => $departments,
            'units' => $units,
            'userShift' => $user->shift,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $user     = $request->user();
        if (!$user->isAdmin()) { abort(403); }
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $query = Attendance::with(['user', 'shift'])
            ->whereBetween('date', [$dateFrom, $dateTo]);

        $attendances = $query->orderBy('date', 'asc')->orderBy('user_id', 'asc')->get();
        $attendances = $attendances->map(fn ($a) => $this->appendPhotoUrls($a));

        $isAdmin     = $user->isAdmin();
        $fromLabel   = Carbon::parse($dateFrom)->format('d-m-Y');
        $toLabel     = Carbon::parse($dateTo)->format('d-m-Y');
        $fromDisplay = Carbon::parse($dateFrom)->format('d F Y');
        $toDisplay   = Carbon::parse($dateTo)->format('d F Y');
        $fileName    = "Rekap_Absensi_{$fromLabel}_sd_{$toLabel}.xlsx";

        $statusLabels = ['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'leave' => 'Cuti', 'sick' => 'Sakit'];
        $statusColors = ['present' => '059669', 'late' => 'D97706', 'absent' => 'E74C3C', 'leave' => '3B82F6', 'sick' => '8B5CF6'];

        $headers  = $isAdmin
            ? ['No', 'Tanggal', 'Hari', 'NIP', 'Nama Karyawan', 'Departemen', 'Shift', 'Jam Shift', 'Clock In', 'Clock Out', 'Status', 'Keterlambatan']
            : ['No', 'Tanggal', 'Hari', 'Shift', 'Jam Shift', 'Clock In', 'Clock Out', 'Status', 'Keterlambatan'];
        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Rekap Absensi');

        $sheet->setCellValue('A1', 'REKAPITULASI ABSENSI KARYAWAN')->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']]);
        $sheet->setCellValue('A2', 'Rumah Sakit Kartika Husada Setu')->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 11], 'alignment' => ['horizontal' => 'center']]);
        $sheet->setCellValue('A3', "Periode: {$fromDisplay} s/d {$toDisplay}")->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->applyFromArray(['alignment' => ['horizontal' => 'center']]);

        $hRow = 5;
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . $hRow, $h);
        }
        $sheet->getStyle("A{$hRow}:{$lastCol}{$hRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F3460']],
            'borders'   => ['allBorders' => ['borderStyle' => 'thin']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        $dRow = $hRow + 1;
        foreach ($attendances as $i => $att) {
            $date        = Carbon::parse($att->date);
            $shiftName   = $att->shift?->name ?? '-';
            $shiftTime   = ($att->shift?->start_time && $att->shift?->end_time)
                ? substr($att->shift->start_time, 0, 5) . ' - ' . substr($att->shift->end_time, 0, 5)
                : '-';
            $statusLabel = $statusLabels[$att->status] ?? $att->status;
            $late        = $att->status === 'late' ? ($att->late_duration ?? '-') : '-';

            $rowData = $isAdmin
                ? [$i + 1, $date->format('d/m/Y'), $date->locale('id')->isoFormat('dddd'),
                   $att->user?->nip ?? '-', $att->user?->name ?? '-', $att->user?->department ?? '-',
                   $shiftName, $shiftTime, $att->clock_in ?? '-', $att->clock_out ?? '-', $statusLabel, $late]
                : [$i + 1, $date->format('d/m/Y'), $date->locale('id')->isoFormat('dddd'),
                   $shiftName, $shiftTime, $att->clock_in ?? '-', $att->clock_out ?? '-', $statusLabel, $late];

            foreach ($rowData as $j => $v) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j + 1) . $dRow, $v);
            }

            $fillColor = ($i % 2 === 1) ? 'F0F4F8' : 'FFFFFF';
            $sheet->getStyle("A{$dRow}:{$lastCol}{$dRow}")->applyFromArray([
                'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => $fillColor]],
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
            ]);

            $statusColIdx = $isAdmin ? 11 : 8;
            $statusCell   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($statusColIdx) . $dRow;
            $statusHex    = $statusColors[$att->status] ?? '374151';
            $sheet->getStyle($statusCell)->getFont()
                  ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF' . $statusHex))->setBold(true);
            $dRow++;
        }

        $dRow++;
        $sheet->setCellValue("A{$dRow}", 'RINGKASAN');
        $sheet->getStyle("A{$dRow}")->getFont()->setBold(true)->setSize(10);
        $dRow++;
        foreach ([
            ['Total Absensi', $attendances->count()],
            ['Hadir',         $attendances->where('status', 'present')->count()],
            ['Terlambat',     $attendances->where('status', 'late')->count()],
            ['Tidak Hadir',   $attendances->where('status', 'absent')->count()],
            ['Cuti',          $attendances->where('status', 'leave')->count()],
            ['Sakit',         $attendances->where('status', 'sick')->count()],
        ] as [$label, $val]) {
            $sheet->setCellValue("A{$dRow}", $label)->setCellValue("B{$dRow}", $val);
            $sheet->getStyle("A{$dRow}")->getFont()->setBold(true);
            $dRow++;
        }

        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmp);
        return response()->download($tmp, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request)
    {
        $user     = $request->user();
        if (!$user->isAdmin()) { abort(403); }
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $query = Attendance::with(['user', 'shift'])
            ->whereBetween('date', [$dateFrom, $dateTo]);

        $attendances = $query->orderBy('date', 'asc')->orderBy('user_id', 'asc')->get();
        $attendances = $attendances->map(fn ($a) => $this->appendPhotoUrls($a));

        $summary = [
            'total'   => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late'    => $attendances->where('status', 'late')->count(),
            'absent'  => $attendances->where('status', 'absent')->count(),
            'leave'   => $attendances->where('status', 'leave')->count(),
            'sick'    => $attendances->where('status', 'sick')->count(),
        ];

        $fromFile = Carbon::parse($dateFrom)->format('d-m-Y');
        $toFile   = Carbon::parse($dateTo)->format('d-m-Y');

        return Inertia::render('Attendance/ExportPDF', [
            'attendances' => $attendances,
            'dateFrom'    => Carbon::parse($dateFrom)->format('d F Y'),
            'dateTo'      => Carbon::parse($dateTo)->format('d F Y'),
            'isAdmin'     => $user->isAdmin(),
            'summary'     => $summary,
            'fileName'    => "Rekap_Absensi_{$fromFile}_sd_{$toFile}.pdf",
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

        // Determine status based on today's schedule
        $status = 'present'; // Default to present
        $shift = null;

        // If schedule exists for today, use that shift
        if ($scheduleToday && $scheduleToday->shift) {
            $shift = $scheduleToday->shift;
            $status = $shift->isLate($now->format('H:i:s')) ? 'late' : 'present';
        }
        // Otherwise, no shift scheduled today = present (not late)

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
            ? '/storage/' . $attendance->photo_in
            : null;

        // URL foto clock out
        $attendance->photo_out_url = $attendance->photo_out
            ? '/storage/' . $attendance->photo_out
            : null;

        // Pastikan shift ter-load jika belum
        if (!$attendance->relationLoaded('shift') && $attendance->shift_id) {
            $attendance->load('shift');
        }

        // Fallback: jika shift masih null, cari dari UserSchedule
        if (!$attendance->shift && $attendance->user_id && $attendance->date) {
            $schedule = \App\Models\UserSchedule::with('shift')
                ->where('user_id', $attendance->user_id)
                ->whereDate('date', $attendance->date)
                ->first();
            if ($schedule && $schedule->shift) {
                $attendance->setRelation('shift', $schedule->shift);
            }
        }

        // Kalkulasi durasi keterlambatan (jam, menit, detik)
        $attendance->late_duration = $this->calculateLateDuration($attendance);
        
        // Jika status late tapi late_duration masih null, coba hitung lagi dengan explicit NIP match
        if ($attendance->status === 'late' && !$attendance->late_duration && $attendance->clock_in && $attendance->date && $attendance->user_id) {
            $user = \App\Models\User::find($attendance->user_id);
            
            if ($user && $user->nip) {
                $schedule = \App\Models\UserSchedule::with('shift')
                    ->whereHas('user', function ($query) use ($user) {
                        $query->where('nip', $user->nip);
                    })
                    ->whereDate('date', $attendance->date)
                    ->first();
                
                if ($schedule && $schedule->shift && $schedule->shift->start_time) {
                    try {
                        $shiftStart = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $schedule->shift->start_time);
                        $clockIn    = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->clock_in);
                        $diffSeconds = abs($clockIn->diffInSeconds($shiftStart, false));
                        
                        if ($diffSeconds > 0) {
                            $hours   = (int) floor($diffSeconds / 3600);
                            $minutes = (int) floor(($diffSeconds % 3600) / 60);
                            $seconds = (int) ($diffSeconds % 60);
                            $attendance->late_duration = "{$hours} jam {$minutes} menit {$seconds} detik";
                        }
                    } catch (\Exception $e) {
                        // Gagal hitung, biarkan null
                    }
                }
            }
        }

        return $attendance;
    }

    /**
     * Hitung durasi keterlambatan dalam format "X jam Y menit Z detik".
     * Match UserSchedule berdasarkan NIP user, bukan user_id.
     */
    private function calculateLateDuration(Attendance $attendance): ?string
    {
        // Hanya hitung jika status = late
        if ($attendance->status !== 'late' || !$attendance->clock_in) {
            return null;
        }

        $shift = $attendance->shift;

        // Fallback: Cari schedule dari UserSchedule berdasarkan NIP user
        if (!$shift && $attendance->user_id && $attendance->date) {
            // Get user dengan join ke UserSchedule via NIP
            $user = \App\Models\User::find($attendance->user_id);
            
            if ($user && $user->nip) {
                // Cari schedule berdasarkan user NIP dan date
                $schedule = \App\Models\UserSchedule::with('shift')
                    ->whereHas('user', function ($query) use ($user) {
                        $query->where('nip', $user->nip);
                    })
                    ->whereDate('date', $attendance->date)
                    ->first();
                
                if ($schedule && $schedule->shift) {
                    $shift = $schedule->shift;
                }
            }
        }

        // Jika tetap tidak ada shift, tidak bisa hitung
        if (!$shift || !$shift->start_time) {
            return null;
        }

        // Parse shift start time dan clock in time
        try {
            $shiftStart = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $shift->start_time);
            $clockIn    = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->clock_in);

            // diffInSeconds returns negative when clockIn is after shiftStart, so use absolute
            $diffSeconds = abs($clockIn->diffInSeconds($shiftStart, false));

            // Jika clock in lebih awal atau sama dengan shift start, tidak terlambat
            if ($diffSeconds <= 0) {
                return null;
            }

            // Hitung jam, menit, detik
            $hours   = (int) floor($diffSeconds / 3600);
            $minutes = (int) floor(($diffSeconds % 3600) / 60);
            $seconds = (int) ($diffSeconds % 60);

            return "{$hours} jam {$minutes} menit {$seconds} detik";
        } catch (\Exception $e) {
            return null;
        }
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
