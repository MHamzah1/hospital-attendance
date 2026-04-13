<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            // Manager sees requests from units they manage at level 2
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
            // Coordinator sees requests from same unit at level 1
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
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
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

        LeaveRequest::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
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

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = LeaveRequest::with(['user.departmentModel', 'user.unitModel']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

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
