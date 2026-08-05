<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LeaveController extends Controller
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

        $query = LeaveRequest::with([
            'user.departmentModel', 'user.unitModel',
            'approver', 'coordinatorApprover', 'managerApprover',
        ]);

        if ($user->isAdmin()) {
            // Admin sees requests at level 3 + already processed
            $query->where(function ($q) {
                $q->where('current_approval_level', 3)
                  ->orWhereIn('status', ['approved', 'rejected']);
            });
        } elseif ($user->isManajer()) {
            // Manager sees requests from units they manage at level 2 + own requests
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
            // Coordinator sees requests from same unit at level 1 + own requests
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
            // Regular staff sees only their own requests
            $query->where('user_id', $user->id);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->where('start_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('end_date', '<=', $dateTo);
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

        $leaves = $query->latest()->paginate(15)->withQueryString();
        $leaves->getCollection()->transform(function ($leave) {
            $leave->attachment_url = $leave->attachment ? '/storage/' . $leave->attachment : null;
            return $leave;
        });

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $units = Unit::orderBy('name')->get(['id', 'name', 'department_id']);

        return Inertia::render('Leave/Index', [
            'leaves' => $leaves,
            'filters' => [
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
                'unit' => $unitFilter,
                'department' => $deptFilter,
            ],
            'typeLabels' => LeaveRequest::typeLabels(),
            'departments' => $departments,
            'units' => $units,
        ]);
    }

    public function create()
    {
        return Inertia::render('Leave/Create', [
            'typeLabels' => LeaveRequest::typeLabels(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:cuti_tahunan,cuti_sakit,cuti_melahirkan,cuti_menikah,cuti_duka,izin_lainnya',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'attachment' => 'required_if:type,cuti_sakit|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'attachment.required_if' => 'Lampiran surat sakit wajib diunggah untuk pengajuan Cuti Sakit.',
            'attachment.mimes' => 'Lampiran harus berformat PDF, JPG, atau PNG.',
            'attachment.max' => 'Ukuran lampiran maksimal 5 MB.',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($validated['type'] === 'cuti_tahunan') {
            $user = $request->user();
            $jatahCuti = $user->jatah_cuti ?? 12;

            $usedLeave = LeaveRequest::where('user_id', $user->id)
                ->where('type', 'cuti_tahunan')
                ->where('status', 'approved')
                ->whereYear('start_date', $startDate->year)
                ->sum('total_days');

            $pendingLeave = LeaveRequest::where('user_id', $user->id)
                ->where('type', 'cuti_tahunan')
                ->where('status', 'pending')
                ->whereYear('start_date', $startDate->year)
                ->sum('total_days');

            $sisaCuti = $jatahCuti - $usedLeave - $pendingLeave;

            if ($totalDays > $sisaCuti) {
                return back()->withErrors(['end_date' => "Sisa jatah cuti tahunan Anda hanya {$sisaCuti} hari (dari {$jatahCuti} hari). Tidak cukup untuk {$totalDays} hari."]);
            }
        }

        // Initial approval level based on user's role
        $initialLevel = $request->user()->getInitialApprovalLevel();

        // KANTOR unit skips koordinator & manager, goes directly to admin (level 3)
        $userUnit = $request->user()->unitModel;
        if ($userUnit && strtoupper(trim($userUnit->name)) === 'KANTOR') {
            $initialLevel = 3;
        }

        // Simpan lampiran surat sakit (jika ada)
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave-attachments', 'public');
        }

        LeaveRequest::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'attachment' => $attachmentPath,
            'status' => 'pending',
            'current_approval_level' => $initialLevel,
        ]);

        return redirect()->route('leaves.index')->with('success', 'Pengajuan cuti berhasil dikirim!');
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $user = $request->user();
        $requester = $leave->user;

        if ($user->isKoordinator() && $leave->current_approval_level === 1 && $user->unit_id === $requester->unit_id) {
            $leave->update([
                'coordinator_approved_by' => $user->id,
                'coordinator_approved_at' => now(),
                'coordinator_notes' => $request->admin_notes,
                'current_approval_level' => 2,
            ]);
            return back()->with('success', 'Pengajuan cuti berhasil disetujui oleh Koordinator. Menunggu persetujuan Manager.');
        }

        if ($user->isManajer() && $leave->current_approval_level === 2) {
            $managedUnitIds = $user->managedUnits()->pluck('id')->toArray();
            if (!in_array($requester->unit_id, $managedUnitIds)) {
                abort(403, 'Anda tidak mengelola unit karyawan ini.');
            }
            $leave->update([
                'manager_approved_by' => $user->id,
                'manager_approved_at' => now(),
                'manager_notes' => $request->admin_notes,
                'current_approval_level' => 3,
            ]);
            return back()->with('success', 'Pengajuan cuti berhasil disetujui oleh Manager. Menunggu persetujuan Admin.');
        }

        if ($user->isAdmin() && $leave->current_approval_level === 3) {
            $leave->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'admin_notes' => $request->admin_notes,
            ]);
            return back()->with('success', 'Cuti berhasil disetujui!');
        }

        abort(403, 'Anda tidak memiliki hak untuk menyetujui pengajuan ini.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $user = $request->user();
        $requester = $leave->user;

        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        if ($user->isKoordinator() && $leave->current_approval_level === 1 && $user->unit_id === $requester->unit_id) {
            $leave->update([
                'status' => 'rejected',
                'coordinator_approved_by' => $user->id,
                'coordinator_approved_at' => now(),
                'coordinator_notes' => $request->admin_notes,
            ]);
            return back()->with('success', 'Pengajuan cuti ditolak oleh Koordinator.');
        }

        if ($user->isManajer() && $leave->current_approval_level === 2) {
            $managedUnitIds = $user->managedUnits()->pluck('id')->toArray();
            if (!in_array($requester->unit_id, $managedUnitIds)) {
                abort(403, 'Anda tidak mengelola unit karyawan ini.');
            }
            $leave->update([
                'status' => 'rejected',
                'manager_approved_by' => $user->id,
                'manager_approved_at' => now(),
                'manager_notes' => $request->admin_notes,
            ]);
            return back()->with('success', 'Pengajuan cuti ditolak oleh Manager.');
        }

        if ($user->isAdmin() && $leave->current_approval_level === 3) {
            $leave->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'admin_notes' => $request->admin_notes,
            ]);
            return back()->with('success', 'Pengajuan cuti ditolak oleh Admin.');
        }

        abort(403, 'Anda tidak memiliki hak untuk menolak pengajuan ini.');
    }

    public function exportExcel(Request $request)
    {
        $user     = $request->user();
        if (!$user->isAdmin()) { abort(403); }
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $query = LeaveRequest::with(['user.departmentModel', 'user.unitModel']);
        if ($dateFrom) { $query->where('start_date', '>=', $dateFrom); }
        if ($dateTo)   { $query->where('end_date', '<=', $dateTo); }

        $leaves       = $query->orderBy('start_date', 'asc')->get();
        $typeLabels   = LeaveRequest::typeLabels();
        $statusLabels = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
        $statusColors = ['approved' => '059669', 'rejected' => 'E74C3C', 'pending' => 'D97706'];

        $isAdmin     = $user->isAdmin();
        $fromLabel   = $dateFrom ? Carbon::parse($dateFrom)->format('d-m-Y') : 'awal';
        $toLabel     = $dateTo   ? Carbon::parse($dateTo)->format('d-m-Y')   : 'akhir';
        $fromDisplay = $dateFrom ? Carbon::parse($dateFrom)->format('d F Y') : '-';
        $toDisplay   = $dateTo   ? Carbon::parse($dateTo)->format('d F Y')   : '-';
        $fileName    = "Rekap_Cuti_{$fromLabel}_sd_{$toLabel}.xlsx";

        $headers  = $isAdmin
            ? ['No', 'Nama Karyawan', 'NIP', 'Unit', 'Jenis Cuti', 'Tgl Mulai', 'Tgl Selesai', 'Total Hari', 'Alasan', 'Status']
            : ['No', 'Jenis Cuti', 'Tgl Mulai', 'Tgl Selesai', 'Total Hari', 'Alasan', 'Status'];
        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Rekap Cuti');

        $sheet->setCellValue('A1', 'REKAP PENGAJUAN CUTI')->mergeCells("A1:{$lastCol}1");
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
        foreach ($leaves as $i => $leave) {
            $rowData = $isAdmin
                ? [$i + 1,
                   $leave->user?->name ?? '-',
                   $leave->user?->nip ?? '-',
                   $leave->user?->unitModel?->name ?? '-',
                   $typeLabels[$leave->type] ?? $leave->type,
                   Carbon::parse($leave->start_date)->format('d/m/Y'),
                   Carbon::parse($leave->end_date)->format('d/m/Y'),
                   $leave->total_days,
                   $leave->reason ?? '-',
                   $statusLabels[$leave->status] ?? $leave->status]
                : [$i + 1,
                   $typeLabels[$leave->type] ?? $leave->type,
                   Carbon::parse($leave->start_date)->format('d/m/Y'),
                   Carbon::parse($leave->end_date)->format('d/m/Y'),
                   $leave->total_days,
                   $leave->reason ?? '-',
                   $statusLabels[$leave->status] ?? $leave->status];

            foreach ($rowData as $j => $v) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j + 1) . $dRow, $v);
            }
            $fillColor = ($i % 2 === 1) ? 'F0F4F8' : 'FFFFFF';
            $sheet->getStyle("A{$dRow}:{$lastCol}{$dRow}")->applyFromArray([
                'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => $fillColor]],
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
            ]);
            $statusColIdx = $isAdmin ? 10 : 7;
            $statusCell   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($statusColIdx) . $dRow;
            $sheet->getStyle($statusCell)->getFont()
                  ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF' . ($statusColors[$leave->status] ?? '374151')))->setBold(true);
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

        $query = LeaveRequest::with(['user.departmentModel', 'user.unitModel']);

        if ($dateFrom) {
            $query->where('start_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('end_date', '<=', $dateTo);
        }

        $leaves = $query->orderBy('start_date', 'asc')->get();

        $typeLabels = LeaveRequest::typeLabels();

        $fromLabel = $dateFrom ? Carbon::parse($dateFrom)->format('d F Y') : '-';
        $toLabel = $dateTo ? Carbon::parse($dateTo)->format('d F Y') : '-';

        return Inertia::render('Leave/ExportPDF', [
            'leaves' => $leaves,
            'dateFrom' => $fromLabel,
            'dateTo' => $toLabel,
            'isAdmin' => $user->isAdmin(),
            'typeLabels' => $typeLabels,
        ]);
    }
}
