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
        $user = $request->user();
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        if ($user->isAdmin()) {
            $payrolls = Payroll::with('user')
                ->where('month', $month)
                ->where('year', $year)
                ->latest()
                ->paginate(20);

            $employees = User::where('role', 'karyawan')
                ->where('status', 'active')
                ->get();

            return Inertia::render('Payroll/Index', [
                'payrolls' => $payrolls,
                'employees' => $employees,
                'filters' => ['month' => (int) $month, 'year' => (int) $year],
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
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $month = $request->month;
        $year = $request->year;

        $employees = User::where('role', 'karyawan')
            ->where('status', 'active')
            ->get();

        // Calculate working days in month (exclude weekends)
        $totalWorkDays = $this->getWorkingDays($month, $year);

        foreach ($employees as $employee) {
            // Skip if already generated
            $existing = Payroll::where('user_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($existing && $existing->status !== 'draft') {
                continue;
            }

            $this->calculatePayroll($employee, $month, $year, $totalWorkDays, $existing);
        }

        return back()->with('success', 'Penggajian berhasil di-generate!');
    }

    private function calculatePayroll(User $employee, int $month, int $year, int $totalWorkDays, ?Payroll $existing = null)
    {
        // Attendance data
        $attendances = Attendance::where('user_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $absentDays = $totalWorkDays - $presentDays - $this->getApprovedLeaveDays($employee->id, $month, $year);
        if ($absentDays < 0) $absentDays = 0;

        // Leave data
        $leaveDays = $this->getApprovedLeaveDays($employee->id, $month, $year);
        $sickDays = $attendances->where('status', 'sick')->count();

        // Overtime data
        $overtimeHours = OvertimeRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('total_hours');

        // === PENDAPATAN ===
        $baseSalary = $employee->base_salary;
        $positionAllowance = $employee->position_allowance;
        $mealAllowance = $employee->meal_allowance * $presentDays; // Per hari hadir
        $transportAllowance = $employee->transport_allowance * $presentDays; // Per hari hadir

        // Lembur: 1/173 x gaji pokok x 1.5 (jam pertama) + 2x (jam berikutnya)
        $hourlyRate = $baseSalary / 173;
        $overtimePay = 0;
        if ($overtimeHours > 0) {
            $firstHour = min($overtimeHours, 1);
            $additionalHours = max($overtimeHours - 1, 0);
            $overtimePay = ($firstHour * $hourlyRate * 1.5) + ($additionalHours * $hourlyRate * 2);
        }

        $grossSalary = $baseSalary + $positionAllowance + $mealAllowance + $transportAllowance + $overtimePay;

        // === POTONGAN ===
        // BPJS Kesehatan: 1% dari gaji pokok (ditanggung karyawan)
        $bpjsKesehatan = round($baseSalary * 0.01);

        // BPJS Ketenagakerjaan (JHT): 2% dari gaji pokok (ditanggung karyawan)
        $bpjsKetenagakerjaan = round($baseSalary * 0.02);

        // BPJS Pensiun: 1% dari gaji pokok (ditanggung karyawan)
        $bpjsPensiun = round($baseSalary * 0.01);

        // Potongan Tidak Hadir (per hari)
        $dailyRate = $baseSalary / $totalWorkDays;
        $absenceDeduction = round($absentDays * $dailyRate);

        // Potongan Terlambat (per kejadian Rp 25.000)
        $lateDeduction = $lateDays * 25000;
        $absenceDeduction += $lateDeduction;

        // PPh 21 Calculation
        // Penghasilan neto setahun
        $annualGross = $grossSalary * 12;
        $annualBPJS = ($bpjsKesehatan + $bpjsKetenagakerjaan + $bpjsPensiun) * 12;
        $biayaJabatan = min($annualGross * 0.05, 6000000); // Max 6jt/tahun

        // PTKP (Penghasilan Tidak Kena Pajak) - TK/0
        $ptkp = 54000000;

        $annualTaxableIncome = $annualGross - $annualBPJS - $biayaJabatan - $ptkp;
        $pph21 = Payroll::calculatePPh21($annualTaxableIncome);

        $totalDeduction = $bpjsKesehatan + $bpjsKetenagakerjaan + $bpjsPensiun + $pph21 + $absenceDeduction;
        $netSalary = $grossSalary - $totalDeduction;

        $data = [
            'user_id' => $employee->id,
            'month' => $month,
            'year' => $year,
            'total_work_days' => $totalWorkDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'leave_days' => $leaveDays,
            'sick_days' => $sickDays,
            'overtime_hours' => $overtimeHours,
            'base_salary' => $baseSalary,
            'position_allowance' => $positionAllowance,
            'meal_allowance' => $mealAllowance,
            'transport_allowance' => $transportAllowance,
            'overtime_pay' => round($overtimePay),
            'other_allowance' => 0,
            'gross_salary' => round($grossSalary),
            'bpjs_kesehatan' => $bpjsKesehatan,
            'bpjs_ketenagakerjaan' => $bpjsKetenagakerjaan,
            'bpjs_pensiun' => $bpjsPensiun,
            'pph21' => round($pph21),
            'absence_deduction' => round($absenceDeduction),
            'other_deduction' => 0,
            'total_deduction' => round($totalDeduction),
            'net_salary' => round($netSalary),
            'status' => 'draft',
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

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $pdf = Pdf::loadView('payroll.slip-pdf', [
            'payroll' => $payroll,
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

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $fileName = "SlipGaji_{$payroll->user->name}_{$months[$payroll->month]}_{$payroll->year}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($payroll, $months) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['SLIP GAJI KARYAWAN']);
            fputcsv($file, ['Rumah Sakit Sehat Sejahtera']);
            fputcsv($file, ['']);
            fputcsv($file, ['Periode', $months[$payroll->month] . ' ' . $payroll->year]);
            fputcsv($file, ['Nama Karyawan', $payroll->user->name]);
            fputcsv($file, ['ID Karyawan', $payroll->user->employee_id]);
            fputcsv($file, ['Departemen', $payroll->user->department]);
            fputcsv($file, ['Jabatan', $payroll->user->position]);
            fputcsv($file, ['']);

            fputcsv($file, ['=== KEHADIRAN ===']);
            fputcsv($file, ['Total Hari Kerja', $payroll->total_work_days]);
            fputcsv($file, ['Hari Hadir', $payroll->present_days]);
            fputcsv($file, ['Hari Terlambat', $payroll->late_days]);
            fputcsv($file, ['Hari Tidak Hadir', $payroll->absent_days]);
            fputcsv($file, ['Hari Cuti', $payroll->leave_days]);
            fputcsv($file, ['Hari Sakit', $payroll->sick_days]);
            fputcsv($file, ['Jam Lembur', $payroll->overtime_hours]);
            fputcsv($file, ['']);

            fputcsv($file, ['=== PENDAPATAN ===']);
            fputcsv($file, ['Gaji Pokok', number_format($payroll->base_salary, 0, ',', '.')]);
            fputcsv($file, ['Tunjangan Jabatan', number_format($payroll->position_allowance, 0, ',', '.')]);
            fputcsv($file, ['Tunjangan Makan', number_format($payroll->meal_allowance, 0, ',', '.')]);
            fputcsv($file, ['Tunjangan Transport', number_format($payroll->transport_allowance, 0, ',', '.')]);
            fputcsv($file, ['Uang Lembur', number_format($payroll->overtime_pay, 0, ',', '.')]);
            fputcsv($file, ['Total Pendapatan', number_format($payroll->gross_salary, 0, ',', '.')]);
            fputcsv($file, ['']);

            fputcsv($file, ['=== POTONGAN ===']);
            fputcsv($file, ['BPJS Kesehatan (1%)', number_format($payroll->bpjs_kesehatan, 0, ',', '.')]);
            fputcsv($file, ['BPJS Ketenagakerjaan (2%)', number_format($payroll->bpjs_ketenagakerjaan, 0, ',', '.')]);
            fputcsv($file, ['BPJS Pensiun (1%)', number_format($payroll->bpjs_pensiun, 0, ',', '.')]);
            fputcsv($file, ['PPh 21', number_format($payroll->pph21, 0, ',', '.')]);
            fputcsv($file, ['Potongan Ketidakhadiran', number_format($payroll->absence_deduction, 0, ',', '.')]);
            fputcsv($file, ['Potongan Lainnya', number_format($payroll->other_deduction, 0, ',', '.')]);
            fputcsv($file, ['Total Potongan', number_format($payroll->total_deduction, 0, ',', '.')]);
            fputcsv($file, ['']);

            fputcsv($file, ['GAJI BERSIH (Take Home Pay)', number_format($payroll->net_salary, 0, ',', '.')]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkExportExcel(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $payrolls = Payroll::with('user')
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $fileName = "Rekap_Gaji_{$months[$month]}_{$year}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($payrolls) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No', 'ID', 'Nama', 'Departemen', 'Jabatan',
                'Hari Kerja', 'Hadir', 'Terlambat', 'Tidak Hadir', 'Cuti', 'Sakit', 'Lembur(Jam)',
                'Gaji Pokok', 'Tunj. Jabatan', 'Tunj. Makan', 'Tunj. Transport', 'Lembur',
                'Total Pendapatan',
                'BPJS Kes', 'BPJS TK', 'BPJS Pensiun', 'PPh21', 'Pot. Absensi', 'Pot. Lain',
                'Total Potongan', 'Gaji Bersih', 'Status'
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
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $days = 0;
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
        $end = $start->copy()->endOfMonth();

        return LeaveRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end]);
            })
            ->sum('total_days');
    }
}
