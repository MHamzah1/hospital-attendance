<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\OvertimeRequest;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $search = $request->get('search', '');
        $unitFilter = $request->get('unit', 'all');
        $deptFilter = $request->get('department', 'all');

        $query = OvertimeRequest::with([
            'user.departmentModel', 'user.unitModel',
            'approver', 'coordinatorApprover', 'managerApprover',
        ]);

        if ($user->isAdmin()) {
            $query->where(function ($q) {
                $q->where('current_approval_level', 3)
                  ->orWhereIn('status', ['approved', 'rejected']);
            });
        } elseif ($user->isManajer()) {
            $managedUnitIds = $user->managedUnits()->pluck('id');
            $query->where(function ($q) use ($user, $managedUnitIds) {
                $q->where(function ($sub) use ($managedUnitIds) {
                    $sub->where('current_approval_level', 2)
                        ->where('status', 'pending')
                        ->whereHas('user', function ($uq) use ($managedUnitIds) {
                            $uq->whereIn('unit_id', $managedUnitIds);
                        });
                })->orWhere('manager_approved_by', $user->id)
                  ->orWhere('user_id', $user->id);
            });
        } elseif ($user->isKoordinator()) {
            $query->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('current_approval_level', 1)
                        ->where('status', 'pending')
                        ->whereHas('user', function ($uq) use ($user) {
                            $uq->where('unit_id', $user->unit_id);
                        });
                })->orWhere('coordinator_approved_by', $user->id)
                  ->orWhere('user_id', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        if ($search && $user->isApprover()) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('nip', 'like', "%{$search}%");
                })->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($unitFilter !== 'all' && $user->isApprover()) {
            $query->whereHas('user', function ($q) use ($unitFilter) {
                $q->where('unit_id', $unitFilter);
            });
        }

        if ($deptFilter !== 'all' && $user->isAdmin()) {
            $query->whereHas('user', function ($q) use ($deptFilter) {
                $q->where('department_id', $deptFilter);
            });
        }

        $overtimes = $query->latest()->paginate(15)->withQueryString();

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $units = Unit::orderBy('name')->get(['id', 'name', 'department_id']);

        return Inertia::render('Overtime/Index', [
            'overtimes' => $overtimes,
            'filters' => [
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
                'unit' => $unitFilter,
                'department' => $deptFilter,
            ],
            'departments' => $departments,
            'units' => $units,
        ]);
    }

    public static array $categories = [
        'lembur'    => 'Lembur',
        'on_call'   => 'On Call',
        'mod'       => 'MOD',
        'hari_raya' => 'Hari Raya',
    ];

    public function create()
    {
        return Inertia::render('Overtime/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
            'category'   => 'required|in:lembur,on_call,mod,hari_raya',
            'reason'     => 'required|string|max:500',
        ]);

        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end   = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $totalHours = round(abs($end->diffInMinutes($start)) / 60, 2);

        // KANTOR unit skips koordinator & manager, goes directly to admin (level 3)
        $userUnit = $request->user()->unitModel;
        $initialLevel = $request->user()->getInitialApprovalLevel();
        if ($userUnit && strtoupper(trim($userUnit->name)) === 'KANTOR') {
            $initialLevel = 3;
        }

        OvertimeRequest::create([
            'user_id'      => $request->user()->id,
            'date'         => $validated['date'],
            'start_time'   => $validated['start_time'],
            'end_time'     => $validated['end_time'],
            'total_hours'  => $totalHours,
            'category'     => $validated['category'],
            'rate_per_hour' => 0,
            'total_pay'    => 0,
            'reason'       => $validated['reason'],
            'status'       => 'pending',
            'current_approval_level' => $initialLevel,
        ]);

        return redirect()->route('overtimes.index')->with('success', 'Pengajuan lembur berhasil dikirim!');
    }

    public function approve(Request $request, OvertimeRequest $overtime)
    {
        $user = $request->user();
        $requester = $overtime->user;

        if ($user->isKoordinator() && $overtime->current_approval_level === 1 && $user->unit_id === $requester->unit_id) {
            $overtime->update([
                'coordinator_approved_by' => $user->id,
                'coordinator_approved_at' => now(),
                'coordinator_notes' => $request->admin_notes,
                'current_approval_level' => 2,
            ]);
            return back()->with('success', 'Pengajuan lembur berhasil disetujui oleh Koordinator. Menunggu persetujuan Manager.');
        }

        if ($user->isManajer() && $overtime->current_approval_level === 2) {
            $managedUnitIds = $user->managedUnits()->pluck('id')->toArray();
            if (!in_array($requester->unit_id, $managedUnitIds)) {
                abort(403, 'Anda tidak mengelola unit karyawan ini.');
            }
            $overtime->update([
                'manager_approved_by' => $user->id,
                'manager_approved_at' => now(),
                'manager_notes' => $request->admin_notes,
                'current_approval_level' => 3,
            ]);
            return back()->with('success', 'Pengajuan lembur berhasil disetujui oleh Manager. Menunggu persetujuan Admin.');
        }

        if ($user->isAdmin() && $overtime->current_approval_level === 3) {
            $request->validate([
                'total_pay' => 'nullable|numeric|min:0',
            ]);

            $updateData = [
                'status'      => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'admin_notes' => $request->admin_notes,
            ];

            if ($request->filled('total_pay')) {
                $updateData['total_pay'] = $request->total_pay;
            }

            $overtime->update($updateData);
            return back()->with('success', 'Lembur berhasil disetujui!');
        }

        abort(403, 'Anda tidak memiliki hak untuk menyetujui pengajuan ini.');
    }

    public function reject(Request $request, OvertimeRequest $overtime)
    {
        $user = $request->user();
        $requester = $overtime->user;

        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        if ($user->isKoordinator() && $overtime->current_approval_level === 1 && $user->unit_id === $requester->unit_id) {
            $overtime->update([
                'status' => 'rejected',
                'coordinator_approved_by' => $user->id,
                'coordinator_approved_at' => now(),
                'coordinator_notes' => $request->admin_notes,
            ]);
            return back()->with('success', 'Pengajuan lembur ditolak oleh Koordinator.');
        }

        if ($user->isManajer() && $overtime->current_approval_level === 2) {
            $managedUnitIds = $user->managedUnits()->pluck('id')->toArray();
            if (!in_array($requester->unit_id, $managedUnitIds)) {
                abort(403, 'Anda tidak mengelola unit karyawan ini.');
            }
            $overtime->update([
                'status' => 'rejected',
                'manager_approved_by' => $user->id,
                'manager_approved_at' => now(),
                'manager_notes' => $request->admin_notes,
            ]);
            return back()->with('success', 'Pengajuan lembur ditolak oleh Manager.');
        }

        if ($user->isAdmin() && $overtime->current_approval_level === 3) {
            $overtime->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'admin_notes' => $request->admin_notes,
            ]);
            return back()->with('success', 'Pengajuan lembur ditolak oleh Admin.');
        }

        abort(403, 'Anda tidak memiliki hak untuk menolak pengajuan ini.');
    }

    public function exportExcel(Request $request)
    {
        $user     = $request->user();
        if (!$user->isAdmin()) { abort(403); }
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $query = OvertimeRequest::with(['user.departmentModel', 'user.unitModel']);
        if ($dateFrom) { $query->where('date', '>=', $dateFrom); }
        if ($dateTo)   { $query->where('date', '<=', $dateTo); }

        $overtimes      = $query->orderBy('date', 'asc')->get();
        $categoryLabels = self::$categories;
        $statusLabels   = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
        $statusColors   = ['approved' => '059669', 'rejected' => 'E74C3C', 'pending' => 'D97706'];

        $isAdmin     = $user->isAdmin();
        $fromLabel   = $dateFrom ? Carbon::parse($dateFrom)->format('d-m-Y') : 'awal';
        $toLabel     = $dateTo   ? Carbon::parse($dateTo)->format('d-m-Y')   : 'akhir';
        $fromDisplay = $dateFrom ? Carbon::parse($dateFrom)->format('d F Y') : '-';
        $toDisplay   = $dateTo   ? Carbon::parse($dateTo)->format('d F Y')   : '-';
        $fileName    = "Rekap_Lembur_{$fromLabel}_sd_{$toLabel}.xlsx";

        $headers  = $isAdmin
            ? ['No', 'Nama Karyawan', 'NIP', 'Unit', 'Tanggal', 'Jam Mulai', 'Jam Selesai', 'Total Jam', 'Kategori', 'Total Bayar', 'Alasan', 'Status']
            : ['No', 'Tanggal', 'Jam Mulai', 'Jam Selesai', 'Total Jam', 'Kategori', 'Alasan', 'Status'];
        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Rekap Lembur');

        $sheet->setCellValue('A1', 'REKAP PENGAJUAN LEMBUR')->mergeCells("A1:{$lastCol}1");
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
        foreach ($overtimes as $i => $ot) {
            $totalPay = $ot->status === 'approved'
                ? 'Rp ' . number_format(abs($ot->total_pay ?? 0), 0, ',', '.')
                : ($ot->status === 'rejected' ? 'Rp 0' : 'Menunggu');
            $rowData = $isAdmin
                ? [$i + 1,
                   $ot->user?->name ?? '-',
                   $ot->user?->nip ?? '-',
                   $ot->user?->unitModel?->name ?? '-',
                   Carbon::parse($ot->date)->format('d/m/Y'),
                   $ot->start_time ?? '-',
                   $ot->end_time ?? '-',
                   abs($ot->total_hours ?? 0),
                   $categoryLabels[$ot->category] ?? $ot->category ?? '-',
                   $totalPay,
                   $ot->reason ?? '-',
                   $statusLabels[$ot->status] ?? $ot->status]
                : [$i + 1,
                   Carbon::parse($ot->date)->format('d/m/Y'),
                   $ot->start_time ?? '-',
                   $ot->end_time ?? '-',
                   abs($ot->total_hours ?? 0),
                   $categoryLabels[$ot->category] ?? $ot->category ?? '-',
                   $ot->reason ?? '-',
                   $statusLabels[$ot->status] ?? $ot->status];

            foreach ($rowData as $j => $v) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j + 1) . $dRow, $v);
            }
            $fillColor = ($i % 2 === 1) ? 'F0F4F8' : 'FFFFFF';
            $sheet->getStyle("A{$dRow}:{$lastCol}{$dRow}")->applyFromArray([
                'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => $fillColor]],
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
            ]);
            $statusColIdx = $isAdmin ? 12 : 8;
            $statusCell   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($statusColIdx) . $dRow;
            $sheet->getStyle($statusCell)->getFont()
                  ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF' . ($statusColors[$ot->status] ?? '374151')))->setBold(true);
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
        $user = $request->user();
        if (!$user->isAdmin()) { abort(403); }
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = OvertimeRequest::with(['user.departmentModel', 'user.unitModel']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $overtimes = $query->orderBy('date', 'asc')->get();

        $fromLabel = $dateFrom ? Carbon::parse($dateFrom)->format('d F Y') : '-';
        $toLabel = $dateTo ? Carbon::parse($dateTo)->format('d F Y') : '-';

        return Inertia::render('Overtime/ExportPDF', [
            'overtimes' => $overtimes,
            'dateFrom' => $fromLabel,
            'dateTo' => $toLabel,
            'isAdmin' => $user->isAdmin(),
        ]);
    }
}
