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

        $query = $user->isAdmin()
            ? LeaveRequest::with('user', 'approver')
            : LeaveRequest::where('user_id', $user->id)->with('approver');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $leaves = $query->latest()->paginate(15);

        return Inertia::render('Leave/Index', [
            'leaves' => $leaves,
            'filters' => ['status' => $status],
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

        // Calculate working days (exclude weekends)
        $totalDays = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if (!$current->isWeekend()) {
                $totalDays++;
            }
            $current->addDay();
        }

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
