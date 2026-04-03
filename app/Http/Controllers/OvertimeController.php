<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
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

        $query = $user->isAdmin()
            ? OvertimeRequest::with('user', 'approver')
            : OvertimeRequest::where('user_id', $user->id)->with('approver');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $overtimes = $query->latest()->paginate(15);

        return Inertia::render('Overtime/Index', [
            'overtimes' => $overtimes,
            'filters' => [
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Overtime/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:500',
        ]);

        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        $totalHours = round($end->diffInMinutes($start) / 60, 2);

        OvertimeRequest::create([
            'user_id' => $request->user()->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_hours' => $totalHours,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('overtimes.index')->with('success', 'Pengajuan lembur berhasil dikirim!');
    }

    public function approve(Request $request, OvertimeRequest $overtime)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $overtime->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'Lembur berhasil disetujui!');
    }

    public function reject(Request $request, OvertimeRequest $overtime)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        $overtime->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'Lembur berhasil ditolak!');
    }
}
