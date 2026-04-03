<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSchedule;
use App\Models\UserScheduleHistory;
use App\Models\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class UserScheduleController extends Controller
{
    /**
     * Show schedule management page
     */
    public function index()
    {
        return Inertia::render('Schedule/Index');
    }

    /**
     * Get monthly schedules for a specific user
     */
    public function getMonthlySchedule(User $user, Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $schedules = UserSchedule::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('shift', 'createdBy')
            ->orderBy('date')
            ->get();

        // Get all available shifts
        $shifts = Shift::where('is_active', true)->get();

        return response()->json([
            'user' => $user->only('id', 'name', 'employee_id'),
            'month' => (int)$month,
            'year' => (int)$year,
            'schedules' => $schedules->map(fn($s) => [
                'id' => $s->id,
                'date' => $s->date->format('Y-m-d'),
                'shift' => $s->shift ? [
                    'id' => $s->shift->id,
                    'name' => $s->shift->name,
                    'time_range' => $s->shift->time_range,
                ] : null,
                'created_by' => $s->createdBy->name,
                'updated_at' => $s->updated_at->format('Y-m-d H:i:s'),
            ]),
            'shifts' => $shifts->map(fn($sh) => [
                'id' => $sh->id,
                'name' => $sh->name,
                'time_range' => $sh->time_range,
            ]),
            'daysInMonth' => Carbon::createFromDate($year, $month, 1)->daysInMonth,
        ]);
    }

    /**
     * Update monthly schedules for a user (with audit trail)
     */
    public function updateMonthlySchedules(User $user, Request $request)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.date' => 'required|date_format:Y-m-d',
            'schedules.*.shift_id' => 'nullable|exists:shifts,id',
        ]);

        $adminId = auth()->user()->id;
        $created = 0;
        $updated = 0;

        foreach ($validated['schedules'] as $schedule) {
            $date = $schedule['date'];
            $shiftId = $schedule['shift_id'];

            // Find existing schedule
            $existing = UserSchedule::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->first();

            $oldShiftId = $existing?->shift_id;

            if ($existing) {
                // Update if shift changed
                if ($existing->shift_id !== $shiftId) {
                    $existing->shift_id = $shiftId;
                    $existing->created_by = $adminId;
                    $existing->save();

                    // Create history record
                    UserScheduleHistory::create([
                        'user_schedule_id' => $existing->id,
                        'shift_id_old' => $oldShiftId,
                        'shift_id_new' => $shiftId,
                        'user_id' => $user->id,
                        'changed_by_id' => $adminId,
                        'changed_at' => now(),
                    ]);

                    $updated++;
                }
            } else {
                if ($shiftId === null) {
                    // Skip empty schedule rows to avoid storing unnecessary null records.
                    continue;
                }

                // Create new schedule
                $newSchedule = UserSchedule::create([
                    'user_id' => $user->id,
                    'shift_id' => $shiftId,
                    'date' => $date,
                    'created_by' => $adminId,
                ]);

                // Create history record
                UserScheduleHistory::create([
                    'user_schedule_id' => $newSchedule->id,
                    'shift_id_old' => null,
                    'shift_id_new' => $shiftId,
                    'user_id' => $user->id,
                    'changed_by_id' => $adminId,
                    'changed_at' => now(),
                ]);

                $created++;
            }
        }

        $total = $created + $updated;
        $monthName = Carbon::createFromDate(
            (int)explode('-', $validated['schedules'][0]['date'])[0],
            (int)explode('-', $validated['schedules'][0]['date'])[1],
            1
        )->format('F Y');

        return response()->json([
            'message' => "Jadwal berhasil diperbarui untuk {$user->name} ({$total} hari di {$monthName})",
            'created' => $created,
            'updated' => $updated,
            'total' => $total,
        ]);
    }

    /**
     * Get schedule history for a user in a month
     */
    public function getHistory(User $user, Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $histories = UserScheduleHistory::where('user_id', $user->id)
            ->whereMonth('changed_at', $month)
            ->whereYear('changed_at', $year)
            ->with('userSchedule', 'changedBy', 'shiftOld', 'shiftNew')
            ->orderByDesc('changed_at')
            ->get();

        return response()->json([
            'user' => $user->only('id', 'name', 'employee_id'),
            'month' => (int)$month,
            'year' => (int)$year,
            'histories' => $histories->map(fn($h) => [
                'id' => $h->id,
                'date' => $h->userSchedule->date->format('Y-m-d'),
                'shift_old' => $h->shiftOld ? [
                    'id' => $h->shiftOld->id,
                    'name' => $h->shiftOld->name,
                    'time_range' => $h->shiftOld->time_range,
                ] : null,
                'shift_new' => $h->shiftNew ? [
                    'id' => $h->shiftNew->id,
                    'name' => $h->shiftNew->name,
                    'time_range' => $h->shiftNew->time_range,
                ] : null,
                'changed_by' => $h->changedBy->name,
                'changed_at' => $h->changed_at->format('Y-m-d H:i:s'),
            ]),
        ]);
    }

    /**
     * List all schedules for a month (admin dashboard)
     */
    public function listAllSchedules($month, $year, Request $request)
    {
        $search = $request->query('search', '');

        // Get all employees
        $employees = User::where('role', 'karyawan')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%"))
            ->get();

        // Get all shifts
        $shifts = Shift::where('is_active', true)->get();

        // Get schedules for the month
        $schedules = UserSchedule::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('user', 'shift')
            ->get()
            ->groupBy('user_id');

        return response()->json([
            'month' => (int)$month,
            'year' => (int)$year,
            'employees' => $employees->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'employee_id' => $e->nip ?: $e->employee_id,
                'department' => $e->department,
                'schedules' => $schedules->get($e->id)?->map(fn($s) => [
                    'date' => $s->date->format('Y-m-d'),
                    'shift' => $s->shift ? [
                        'id' => $s->shift->id,
                        'name' => $s->shift->name,
                    ] : null,
                ]) ?? [],
            ]),
            'shifts' => $shifts->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'time_range' => $s->time_range,
            ]),
            'daysInMonth' => Carbon::createFromDate($year, $month, 1)->daysInMonth,
        ]);
    }

    /**
     * Download (OK JADWAL) Excel template with existing schedule pre-filled
     */
    public function downloadTemplate(Request $request)
    {
        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $shifts    = Shift::where('is_active', true)->orderBy('id')->get();
        $employees = User::where('role', 'karyawan')->orderBy('name')->get();

        // Pre-load existing schedules
        $existingSchedules = UserSchedule::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('shift')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($items) => $items->keyBy(fn ($s) => $s->date->day));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('JADWAL');

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $daysInMonth);

        // ── Row 1: Legend ──────────────────────────────────────────────────
        $shiftLegend = $shifts->map(fn ($s) => $this->getShiftCode($s->name) . ' = ' . $s->name)->join(' | ');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', "OK JADWAL | Kode Shift: {$shiftLegend} | L = Libur / Tidak Dijadwalkan");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // ── Row 2: Column Headers ──────────────────────────────────────────
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2F5496']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
        ];
        $fixedHeaders = ['A' => 'NIP', 'B' => 'NAMA KARYAWAN'];
        foreach ($fixedHeaders as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
            $sheet->getStyle($col . '2')->applyFromArray($headerStyle);
        }

        $weekendStyle = array_merge($headerStyle, ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C55A11']]]);
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $col  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $day);
            $date = Carbon::createFromDate($year, $month, $day);
            $sheet->setCellValue($col . '2', $day);
            $sheet->getStyle($col . '2')->applyFromArray($date->isWeekend() ? $weekendStyle : $headerStyle);
        }
        $sheet->getRowDimension(2)->setRowHeight(20);

        // ── Rows 3+: Employee data ─────────────────────────────────────────
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
        ];
        $row = 3;
        foreach ($employees as $emp) {
            $nipValue = $emp->nip ?: $emp->employee_id ?: $emp->id;
            $sheet->setCellValue('A' . $row, $nipValue);
            $sheet->setCellValue('B' . $row, $emp->name);

            $empSchedules = $existingSchedules->get($emp->id) ?? collect();
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $col  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $day);
                $sched = $empSchedules->get($day);
                if ($sched && $sched->shift) {
                    $sheet->setCellValue($col . $row, $this->getShiftCode($sched->shift->name));
                }
            }

            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($dataStyle);
            // Stripe even rows
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType('solid')->getStartColor()->setRGB('F2F7FF');
            }
            $row++;
        }

        // ── Column widths ──────────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(24);
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $day);
            $sheet->getColumnDimension($col)->setWidth(5);
        }
        $sheet->freezePane('C3');

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'ok_jadwal_');
        $writer->save($tempFile);

        $monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $monthName = ($monthNames[$month] ?? '') . ' ' . $year;
        return response()->download($tempFile, "(OK JADWAL) {$monthName}.xlsx")->deleteFileAfterSend(true);
    }

    /**
     * Import schedule from (OK JADWAL) Excel/CSV file
     */
    public function importSchedule(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'file'  => 'required|file',
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020|max:2030',
        ]);

        $month   = (int) $request->get('month');
        $year    = (int) $request->get('year');
        $adminId = auth()->id();

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return response()->json(['success' => false, 'message' => 'Format file tidak didukung. Gunakan XLSX, XLS, atau CSV.'], 422);
        }

        try {
            // ── Read rows from file ──────────────────────────────────────
            if ($ext === 'csv') {
                $rows = [];
                $handle = fopen($file->getPathname(), 'r');
                while (($row = fgetcsv($handle)) !== false) {
                    $rows[] = $row;
                }
                fclose($handle);
            } else {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            }

            // ── Find header row (where column A = "NIP") ────────────────
            $headerRowIndex = null;
            $dayColumns = []; // index => day_number
            foreach ($rows as $i => $row) {
                $colA = isset($row[0]) ? trim((string)$row[0]) : '';
                if (strtoupper($colA) === 'NIP') {
                    $headerRowIndex = $i;
                    // Map column indexes to day numbers (dates start from column C = index 2)
                    foreach ($row as $colIdx => $val) {
                        if ($colIdx >= 2 && is_numeric(trim((string)$val))) {
                            $dayColumns[$colIdx] = (int) trim((string)$val);
                        }
                    }
                    break;
                }
            }

            if ($headerRowIndex === null) {
                return response()->json(['success' => false, 'message' => 'Format file tidak valid. Pastikan baris header mengandung kolom "NIP".'], 422);
            }

            // ── Load all active shifts ───────────────────────────────────
            $shifts = Shift::where('is_active', true)->get();

            // ── Process data rows ────────────────────────────────────────
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors  = [];
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            foreach ($rows as $i => $row) {
                if ($i <= $headerRowIndex) continue;
                if (empty(array_filter(array_map('strval', $row)))) continue;

                $nip = trim((string)($row[0] ?? ''));
                if (!$nip) {
                    $skipped++;
                    continue;
                }

                // Find employee by NIP only
                $employee = User::where('nip', $nip)->first();
                if (!$employee) {
                    $errors[] = "Baris " . ($i + 1) . ": NIP '{$nip}' tidak ditemukan.";
                    continue;
                }

                // Process each date column
                foreach ($dayColumns as $colIdx => $day) {
                    if ($day < 1 || $day > $daysInMonth) continue;

                    $cellValue = trim((string)($row[$colIdx] ?? ''));
                    $dateStr   = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');

                    // Determine shift
                    $shift = $this->findShiftByCode($cellValue, $shifts);
                    // "" and "L" → null shift (clear schedule)
                    $shiftId = $shift?->id;

                    $existing = UserSchedule::where('user_id', $employee->id)
                        ->whereDate('date', $dateStr)
                        ->first();

                    if ($existing) {
                        if ($existing->shift_id !== $shiftId) {
                            $oldShiftId = $existing->shift_id;
                            $existing->update(['shift_id' => $shiftId, 'created_by' => $adminId]);
                            UserScheduleHistory::create([
                                'user_schedule_id' => $existing->id,
                                'shift_id_old'     => $oldShiftId,
                                'shift_id_new'     => $shiftId,
                                'user_id'          => $employee->id,
                                'changed_by_id'    => $adminId,
                                'changed_at'       => now(),
                            ]);
                            $updated++;
                        }
                    } else {
                        if ($shiftId === null) continue; // skip empty
                        $newSchedule = UserSchedule::create([
                            'user_id'    => $employee->id,
                            'shift_id'   => $shiftId,
                            'date'       => $dateStr,
                            'created_by' => $adminId,
                        ]);
                        UserScheduleHistory::create([
                            'user_schedule_id' => $newSchedule->id,
                            'shift_id_old'     => null,
                            'shift_id_new'     => $shiftId,
                            'user_id'          => $employee->id,
                            'changed_by_id'    => $adminId,
                            'changed_at'       => now(),
                        ]);
                        $created++;
                    }
                }
            }

            $monthNames = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
            $monthName = ($monthNames[$month] ?? '') . ' ' . $year;
            return response()->json([
                'success'  => true,
                'message'  => "Import jadwal {$monthName} selesai: {$created} jadwal baru, {$updated} diupdate.",
                'created'  => $created,
                'updated'  => $updated,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 422);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getShiftCode(string $shiftName): string
    {
        $parts = explode(' ', trim($shiftName));
        if (count($parts) > 1) {
            $type = $parts[1];
            if (strtolower($type) === 'middle') return 'MD';
            return strtoupper(mb_substr($type, 0, 1));
        }
        return strtoupper(mb_substr($shiftName, 0, 1));
    }

    private function findShiftByCode(string $code, $shifts): ?\App\Models\Shift
    {
        $code = trim($code);
        if ($code === '' || $code === '-' || strtolower($code) === 'l' || strtolower($code) === 'libur') {
            return null;
        }
        // Match by generated code (e.g., "P", "S", "M", "MD")
        foreach ($shifts as $shift) {
            if ($this->getShiftCode($shift->name) === strtoupper($code)) {
                return $shift;
            }
        }
        // Match by full name
        foreach ($shifts as $shift) {
            if (strtolower($shift->name) === strtolower($code)) {
                return $shift;
            }
        }
        // Partial name match
        foreach ($shifts as $shift) {
            if (stripos($shift->name, $code) !== false) {
                return $shift;
            }
        }
        return null;
    }

    /**
     * Bulk update schedules for multiple users in a month
     */
    public function bulkUpdateSchedules(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
            'schedules' => 'required|array',
            'schedules.*.user_id' => 'required|exists:users,id',
            'schedules.*.date' => 'required|date_format:Y-m-d',
            'schedules.*.shift_id' => 'nullable|exists:shifts,id',
        ]);

        $adminId = auth()->user()->id;
        $created = 0;
        $updated = 0;

        try {
            foreach ($validated['schedules'] as $schedule) {
                $date = $schedule['date'];
                $userId = $schedule['user_id'];
                $shiftId = $schedule['shift_id'];

                // Find existing schedule
                $existing = UserSchedule::where('user_id', $userId)
                    ->whereDate('date', $date)
                    ->first();

                $oldShiftId = $existing?->shift_id;

                if ($existing) {
                    // Update if shift changed
                    if ($existing->shift_id !== $shiftId) {
                        $existing->shift_id = $shiftId;
                        $existing->created_by = $adminId;
                        $existing->save();

                        // Create history record
                        UserScheduleHistory::create([
                            'user_schedule_id' => $existing->id,
                            'shift_id_old' => $oldShiftId,
                            'shift_id_new' => $shiftId,
                            'user_id' => $userId,
                            'changed_by_id' => $adminId,
                            'changed_at' => now(),
                        ]);

                        $updated++;
                    }
                } else {
                    if ($shiftId === null) {
                        // Skip empty schedule rows to avoid storing unnecessary null records.
                        continue;
                    }

                    // Create new schedule
                    $newSchedule = UserSchedule::create([
                        'user_id' => $userId,
                        'shift_id' => $shiftId,
                        'date' => $date,
                        'created_by' => $adminId,
                    ]);

                    // Create history record
                    UserScheduleHistory::create([
                        'user_schedule_id' => $newSchedule->id,
                        'shift_id_old' => null,
                        'shift_id_new' => $shiftId,
                        'user_id' => $userId,
                        'changed_by_id' => $adminId,
                        'changed_at' => now(),
                    ]);

                    $created++;
                }
            }

            return response()->json([
                'message' => "Berhasil menyimpan jadwal: {$created} baru, {$updated} diupdate",
                'created' => $created,
                'updated' => $updated,
                'total' => $created + $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }
}

