<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);

        if ($user->isAdmin()) {
            $query = Payroll::with('user')
                ->where('month', $month)
                ->where('year', $year);

            // Search filter
            if ($search = $request->get('search')) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%")
                      ->orWhere('department', 'like', "%{$search}%");
                });
            }

            $payrolls = $query->latest()->get();

            $employees = User::where('role', 'karyawan')
                ->where('status', '!=', 'keluar')
                ->get();

            // Summary totals (semua data, bukan hanya halaman ini)
            $summary = [
                'total_employees' => Payroll::where('month', $month)->where('year', $year)->count(),
                'total_bruto'     => (int) Payroll::where('month', $month)->where('year', $year)->sum('gross_salary'),
                'total_netto'     => (int) Payroll::where('month', $month)->where('year', $year)->sum('net_salary'),
            ];

            return Inertia::render('Payroll/Index', [
                'payrolls'  => $payrolls,
                'employees' => $employees,
                'filters'   => ['month' => (int) $month, 'year' => (int) $year, 'search' => $search ?? ''],
                'summary'   => $summary,
            ]);
        }

        $payrolls = Payroll::where('user_id', $user->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(12);

        return Inertia::render('Payroll/EmployeePayroll', [
            'payrolls' => $payrolls,
        ]);
    }

    public function generate(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020|max:2030',
        ]);

        $month = $request->month;
        $year  = $request->year;

        $employees = User::where('role', 'karyawan')
            ->where('status', '!=', 'keluar')
            ->get();

        $totalWorkDays = $this->getWorkingDays($month, $year);

        foreach ($employees as $employee) {
            // Ambil payroll existing sekali saja — dipakai di calculatePayroll
            $existing = Payroll::where('user_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            // Lewati jika sudah finalized atau paid
            if ($existing && !in_array($existing->status, ['draft', null])) {
                continue;
            }

            $this->calculatePayroll($employee, $month, $year, $totalWorkDays, $existing);
        }

        return back()->with('success', 'Penggajian berhasil di-generate!');
    }

    private function calculatePayroll(User $employee, int $month, int $year, int $totalWorkDays, ?Payroll $existing = null)
    {
        // Data kehadiran
        $attendances = Attendance::where('user_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
        $lateDays    = $attendances->where('status', 'late')->count();
        $leaveDays   = $this->getApprovedLeaveDays($employee->id, $month, $year);
        $sickDays    = $attendances->where('status', 'sick')->count();

        $absentDays = $totalWorkDays - $presentDays - $leaveDays;
        if ($absentDays < 0) $absentDays = 0;

        // === PENDAPATAN (SELALU dari master karyawan) ===
        $baseSalary           = $employee->base_salary ?? 0;
        $positionAllowance    = $employee->position_allowance ?? 0;
        $functionalAllowance  = $employee->functional_allowance ?? 0;
        $specialAllowance     = $employee->special_allowance ?? 0;
        $mealAllowance        = $employee->meal_allowance ?? 0;
        $transportAllowance   = $employee->transport_allowance ?? 0;
        $attendanceAllowance  = $employee->attendance_allowance ?? 0;

        // BRUTO = Gaji Pokok + Semua Tunjangan
        $grossSalary = $baseSalary + $positionAllowance + $functionalAllowance
                     + $specialAllowance + $mealAllowance + $transportAllowance + $attendanceAllowance;

        // === LEMBUR (dari OvertimeRequest yang approved, per kategori) ===
        $overtimeRequests = OvertimeRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $overtimeHours   = $overtimeRequests->sum('total_hours');
        $overtimeHourly  = $overtimeRequests->where('category', 'jam')->sum('total_pay');
        $overtimeNight   = $overtimeRequests->where('category', 'malam')->sum('total_pay');
        $overtimeShift   = $overtimeRequests->where('category', 'shift')->sum('total_pay');
        $overtimeOnCall  = $overtimeRequests->where('category', 'on_call')->sum('total_pay');
        $overtimeMod     = $overtimeRequests->where('category', 'mod')->sum('total_pay');
        $overtimeHoliday = $overtimeRequests->where('category', 'hari_raya')->sum('total_pay');

        $totalOvertimeOther = $overtimeHourly + $overtimeNight + $overtimeShift
                            + $overtimeOnCall + $overtimeMod + $overtimeHoliday;

        // Koreksi upah & lain-lain: pertahankan dari existing jika ada (diisi via import)
        $salaryCorrection = $existing?->salary_correction ?? 0;
        $otherAllowance   = $existing?->other_allowance ?? 0;

        // TOTAL PENDAPATAN = BRUTO + Lembur + Koreksi Upah(+) + Lain-lain(+)
        $totalPendapatan = $grossSalary + $totalOvertimeOther + $salaryCorrection + $otherAllowance;

        // === POTONGAN ===
        // BPJS auto-kalkulasi dari gaji pokok
        $bpjsKesehatan      = round($baseSalary * 0.01);
        $bpjsKetenagakerjaan = round($baseSalary * 0.02);
        $bpjsPensiunJp      = round($baseSalary * 0.01);

        // Potongan ketidakhadiran dihilangkan sesuai permintaan

        // Potongan admin: pertahankan dari existing (diisi via import)
        $cdtDeduction                = $existing?->cdt_deduction ?? 0;
        $alphaDeduction              = $existing?->alpha_deduction ?? 0;
        $cashbondDeduction           = $existing?->cashbond_deduction ?? 0;
        $piutangObatDeduction        = $existing?->piutang_obat_deduction ?? 0;
        $salaryCorrectDeduction      = $existing?->salary_correction_deduction ?? 0;
        $bankAdminDeduction          = $existing?->bank_admin_deduction ?? 0;
        $otherDeduction              = $existing?->other_deduction ?? 0;

        // PPh 21
        $annualGross         = $totalPendapatan * 12;
        $annualBPJS          = ($bpjsKesehatan + $bpjsKetenagakerjaan + $bpjsPensiunJp) * 12;
        $biayaJabatan        = min($annualGross * 0.05, 6000000);
        $ptkp                = 54000000; // TK/0
        $annualTaxableIncome = $annualGross - $annualBPJS - $biayaJabatan - $ptkp;
        $pph21               = Payroll::calculatePPh21($annualTaxableIncome);

        // Total Potongan
        $totalDeduction = $bpjsKesehatan + $bpjsKetenagakerjaan + $bpjsPensiunJp
                        + round($pph21)
                        + $cdtDeduction + $alphaDeduction + $cashbondDeduction
                        + $piutangObatDeduction + $salaryCorrectDeduction
                        + $bankAdminDeduction + $otherDeduction;

        // GAJI DIBAYARKAN
        $netSalary = $totalPendapatan - $totalDeduction;

        $data = [
            'user_id'              => $employee->id,
            'month'                => $month,
            'year'                 => $year,
            'total_work_days'      => $totalWorkDays,
            'present_days'         => $presentDays,
            'absent_days'          => $absentDays,
            'late_days'            => $lateDays,
            'leave_days'           => $leaveDays,
            'sick_days'            => $sickDays,
            'overtime_hours'       => $overtimeHours,

            // Pendapatan
            'base_salary'          => $baseSalary,
            'position_allowance'   => $positionAllowance,
            'functional_allowance' => $functionalAllowance,
            'special_allowance'    => $specialAllowance,
            'meal_allowance'       => $mealAllowance,
            'transport_allowance'  => $transportAllowance,
            'attendance_allowance' => $attendanceAllowance,
            'gross_salary'         => round($grossSalary),

            // Lembur per kategori
            'overtime_hourly'      => round($overtimeHourly),
            'overtime_night'       => round($overtimeNight),
            'overtime_shift'       => round($overtimeShift),
            'overtime_on_call'     => round($overtimeOnCall),
            'overtime_mod'         => round($overtimeMod),
            'overtime_holiday'     => round($overtimeHoliday),
            'overtime_pay'         => round($totalOvertimeOther),
            'total_overtime_other' => round($totalOvertimeOther),

            // Tambahan
            'salary_correction'    => $salaryCorrection,
            'other_allowance'      => $otherAllowance,

            // Potongan
            'bpjs_kesehatan'              => $bpjsKesehatan,
            'bpjs_ketenagakerjaan'        => $bpjsKetenagakerjaan,
            'bpjs_pensiun'                => $bpjsPensiunJp,
            'bpjs_pensiun_jp'             => $bpjsPensiunJp,
            'pph21'                       => round($pph21),
            'absence_deduction'           => 0,
            'cdt_deduction'               => $cdtDeduction,
            'alpha_deduction'             => $alphaDeduction,
            'cashbond_deduction'          => $cashbondDeduction,
            'piutang_obat_deduction'      => $piutangObatDeduction,
            'salary_correction_deduction' => $salaryCorrectDeduction,
            'bank_admin_deduction'        => $bankAdminDeduction,
            'other_deduction'             => $otherDeduction,
            'total_deduction'             => round($totalDeduction),

            'net_salary'           => round($netSalary),
            'status'               => 'draft',
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            Payroll::create($data);
        }
    }

    public function show(Payroll $payroll, Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $payroll->user_id !== $user->id) {
            abort(403);
        }

        $payroll->load('user');

        return Inertia::render('Payroll/SlipGaji', [
            'payroll' => $payroll,
        ]);
    }

    public function finalize(Request $request, Payroll $payroll)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $payroll->update(['status' => 'finalized']);
        return back()->with('success', 'Payroll berhasil di-finalisasi!');
    }

    public function markPaid(Request $request, Payroll $payroll)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $payroll->update(['status' => 'paid']);
        return back()->with('success', 'Payroll berhasil ditandai sebagai dibayar!');
    }

    public function exportPdf(Payroll $payroll, Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $payroll->user_id !== $user->id) {
            abort(403);
        }

        $payroll->load('user');

        $months = $this->monthNames();

        $pdf      = Pdf::loadView('payroll.slip-pdf', [
            'payroll'   => $payroll,
            'monthName' => $months[$payroll->month],
        ]);
        $fileName = "SlipGaji_{$payroll->user->name}_{$months[$payroll->month]}_{$payroll->year}.pdf";

        return $pdf->download($fileName);
    }

    public function exportExcel(Payroll $payroll, Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $payroll->user_id !== $user->id) {
            abort(403);
        }

        $payroll->load('user');
        $months   = $this->monthNames();
        $fileName = "SlipGaji_{$payroll->user->name}_{$months[$payroll->month]}_{$payroll->year}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($payroll, $months) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            $f = fn($n) => number_format($n, 0, ',', '.');

            fputcsv($file, ['SLIP GAJI KARYAWAN']);
            fputcsv($file, ['Rumah Sakit Kartika Husada Setu']);
            fputcsv($file, ['']);
            fputcsv($file, ['Periode',        $months[$payroll->month] . ' ' . $payroll->year]);
            fputcsv($file, ['NIP',            $payroll->user->nip ?? $payroll->user->employee_id]);
            fputcsv($file, ['Nama Karyawan',  $payroll->user->name]);
            fputcsv($file, ['Unit / Dept.',   $payroll->user->department]);
            fputcsv($file, ['Jabatan',        $payroll->user->position]);
            fputcsv($file, ['']);

            fputcsv($file, ['=== KEHADIRAN ===']);
            fputcsv($file, ['Total Hari Kerja',  $payroll->total_work_days]);
            fputcsv($file, ['Hari Hadir',        $payroll->present_days]);
            fputcsv($file, ['Hari Terlambat',    $payroll->late_days]);
            fputcsv($file, ['Hari Tidak Hadir',  $payroll->absent_days]);
            fputcsv($file, ['Hari Cuti',         $payroll->leave_days]);
            fputcsv($file, ['Hari Sakit',        $payroll->sick_days]);
            fputcsv($file, ['']);

            fputcsv($file, ['=== PENDAPATAN ===']);
            fputcsv($file, ['Gaji Pokok',          $f($payroll->base_salary)]);
            fputcsv($file, ['Tunj. Jabatan',       $f($payroll->position_allowance)]);
            fputcsv($file, ['Tunj. Fungsional',    $f($payroll->functional_allowance)]);
            fputcsv($file, ['Tunj. Khusus',        $f($payroll->special_allowance)]);
            fputcsv($file, ['Tunj. Makan',         $f($payroll->meal_allowance)]);
            fputcsv($file, ['Tunj. Transport',     $f($payroll->transport_allowance)]);
            fputcsv($file, ['Tunj. Kehadiran',     $f($payroll->attendance_allowance)]);
            fputcsv($file, ['BRUTO',               $f($payroll->gross_salary)]);
            fputcsv($file, ['']);
            fputcsv($file, ['Lembur Jam',          $f($payroll->overtime_hourly)]);
            fputcsv($file, ['Lembur Malam',        $f($payroll->overtime_night)]);
            fputcsv($file, ['Lembur Shift',        $f($payroll->overtime_shift)]);
            fputcsv($file, ['Lembur On Call',      $f($payroll->overtime_on_call)]);
            fputcsv($file, ['Lembur MOD',          $f($payroll->overtime_mod)]);
            fputcsv($file, ['Lembur Hari Raya',    $f($payroll->overtime_holiday)]);
            fputcsv($file, ['Koreksi Upah (+)',    $f($payroll->salary_correction)]);
            fputcsv($file, ['Lain-lain (+)',       $f($payroll->other_allowance)]);

            $totalPendapatan = $payroll->gross_salary + $payroll->overtime_hourly + $payroll->overtime_night
                + $payroll->overtime_shift + $payroll->overtime_on_call + $payroll->overtime_mod
                + $payroll->overtime_holiday + $payroll->salary_correction + $payroll->other_allowance;
            fputcsv($file, ['TOTAL PENDAPATAN',    $f($totalPendapatan)]);
            fputcsv($file, ['']);

            fputcsv($file, ['=== POTONGAN ===']);
            fputcsv($file, ['CDT',                         $f($payroll->cdt_deduction)]);
            fputcsv($file, ['Alpa',                        $f($payroll->alpha_deduction)]);
            fputcsv($file, ['Cashbond',                    $f($payroll->cashbond_deduction)]);
            fputcsv($file, ['Piutang Obat',                $f($payroll->piutang_obat_deduction)]);
            fputcsv($file, ['Koreksi Upah (-)',            $f($payroll->salary_correction_deduction)]);
            fputcsv($file, ['Adm. Bank',                   $f($payroll->bank_admin_deduction)]);
            fputcsv($file, ['PPh 21',                      $f($payroll->pph21)]);
            fputcsv($file, ['Pot. Ketidakhadiran',         $f($payroll->absence_deduction)]);
            fputcsv($file, ['Potongan Lainnya',            $f($payroll->other_deduction)]);
            fputcsv($file, ['BPJS Kesehatan (1%)',         $f($payroll->bpjs_kesehatan)]);
            fputcsv($file, ['BPJS TK JHT (2%)',           $f($payroll->bpjs_ketenagakerjaan)]);
            fputcsv($file, ['BPJS TK JP (1%)',            $f($payroll->bpjs_pensiun_jp ?? $payroll->bpjs_pensiun)]);
            fputcsv($file, ['Total Potongan',              $f($payroll->total_deduction)]);
            fputcsv($file, ['']);

            fputcsv($file, ['GAJI DIBAYARKAN (Take Home Pay)', $f($payroll->net_salary)]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkExportExcel(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $month  = $request->get('month', Carbon::now()->month);
        $year   = $request->get('year', Carbon::now()->year);
        $months = $this->monthNames();

        $payrolls = Payroll::with('user')
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $fileName = "Rekap_Gaji_{$months[$month]}_{$year}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($payrolls) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No', 'ID', 'Nama', 'Departemen', 'Jabatan',
                'Hari Kerja', 'Hadir', 'Terlambat', 'Tidak Hadir', 'Cuti', 'Sakit', 'Lembur(Jam)',
                'Gaji Pokok', 'Tunj. Jabatan', 'Tunj. Makan', 'Tunj. Transport', 'Lembur',
                'Total Pendapatan',
                'BPJS Kes', 'BPJS TK', 'BPJS Pensiun', 'PPh21', 'Pot. Absensi', 'Pot. Lain',
                'Total Potongan', 'Gaji Bersih', 'Status',
            ]);

            foreach ($payrolls as $i => $p) {
                fputcsv($file, [
                    $i + 1, $p->user->employee_id, $p->user->name, $p->user->department, $p->user->position,
                    $p->total_work_days, $p->present_days, $p->late_days, $p->absent_days, $p->leave_days, $p->sick_days, $p->overtime_hours,
                    $p->base_salary, $p->position_allowance, $p->meal_allowance, $p->transport_allowance, $p->overtime_pay,
                    $p->gross_salary,
                    $p->bpjs_kesehatan, $p->bpjs_ketenagakerjaan, $p->bpjs_pensiun, $p->pph21, $p->absence_deduction, $p->other_deduction,
                    $p->total_deduction, $p->net_salary, $p->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getWorkingDays(int $month, int $year): int
    {
        $start   = Carbon::create($year, $month, 1);
        $end     = $start->copy()->endOfMonth();
        $days    = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (!$current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    private function getApprovedLeaveDays(int $userId, int $month, int $year): int
    {
        $start = Carbon::create($year, $month, 1);
        $end   = $start->copy()->endOfMonth();

        return LeaveRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            })
            ->sum('total_days');
    }

    private function monthNames(): array
    {
        return [
            1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
            4  => 'April',    5  => 'Mei',       6  => 'Juni',
            7  => 'Juli',     8  => 'Agustus',   9  => 'September',
            10 => 'Oktober',  11 => 'November',  12 => 'Desember',
        ];
    }
}
