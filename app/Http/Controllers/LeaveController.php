<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
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

        $query = $user->isAdmin()
            ? LeaveRequest::with('user', 'approver')
            : LeaveRequest::where('user_id', $user->id)->with('approver');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->where('start_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('end_date', '<=', $dateTo);
        }

        if ($search && $user->isAdmin()) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('nip', 'like', "%{$search}%");
                })->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $leaves = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Leave/Index', [
            'leaves' => $leaves,
            'filters' => [
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
            ],
            'typeLabels' => LeaveRequest::typeLabels(),
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

        // Hitung semua hari kalender (termasuk sabtu, minggu, tanggal merah)
        $totalDays = $startDate->diffInDays($endDate) + 1;

        LeaveRequest::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('leaves.index')->with('success', 'Pengajuan cuti berhasil dikirim!');
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $leave->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'Cuti berhasil disetujui!');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        $leave->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'Cuti berhasil ditolak!');
    }
}
