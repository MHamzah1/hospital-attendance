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
                })->orWhere('manager_approved_by', $user->id);
            });
        } elseif ($user->isKoordinator()) {
            $query->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('current_approval_level', 1)
                        ->where('status', 'pending')
                        ->whereHas('user', function ($uq) use ($user) {
                            $uq->where('unit_id', $user->unit_id);
                        });
                })->orWhere('coordinator_approved_by', $user->id);
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
            'current_approval_level' => $request->user()->getInitialApprovalLevel(),
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

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = OvertimeRequest::with(['user.departmentModel', 'user.unitModel']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

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
