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
        $search = $request->get('search', '');
        $unit = $request->get('unit', 'all');

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

        if ($search && $user->isAdmin()) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('nip', 'like', "%{$search}%");
                })->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($unit !== 'all' && $user->isAdmin()) {
            $query->whereHas('user', function ($q) use ($unit) {
                $q->where('unit', $unit);
            });
        }

        $overtimes = $query->latest()->paginate(15)->withQueryString();

        $units = \App\Models\User::where('role', 'karyawan')
            ->whereNotNull('unit')
            ->where('unit', '!=', '')
            ->distinct()
            ->pluck('unit');

        return Inertia::render('Overtime/Index', [
            'overtimes' => $overtimes,
            'filters' => [
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
                'unit' => $unit,
            ],
            'units' => $units,
        ]);
    }

    // Tarif default per kategori (Rp)
    public static array $categoryRates = [
        'jam'        => 10000,
        'malam'      => 20000,
        'shift'      => 80000,
        'on_call'    => 50000,
        'mod'        => 100000,
        'hari_raya'  => 120000,
    ];

    public function create()
    {
        return Inertia::render('Overtime/Create', [
            'categoryRates' => self::$categoryRates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
            'category'   => 'required|in:jam,malam,shift,on_call,mod,hari_raya',
            'reason'     => 'required|string|max:500',
        ]);

        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end   = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);

        // Handle overnight shifts (end time < start time)
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $totalHours = round(abs($end->diffInMinutes($start)) / 60, 2);
        $rate       = self::$categoryRates[$validated['category']] ?? 10000;

        // Jam = per hour × total hours; semua kategori lain = flat rate per shift
        $totalPay = $validated['category'] === 'jam'
            ? $totalHours * $rate
            : $rate;

        OvertimeRequest::create([
            'user_id'      => $request->user()->id,
            'date'         => $validated['date'],
            'start_time'   => $validated['start_time'],
            'end_time'     => $validated['end_time'],
            'total_hours'  => $totalHours,
            'category'     => $validated['category'],
            'rate_per_hour' => $rate,
            'total_pay'    => $totalPay,
            'reason'       => $validated['reason'],
            'status'       => 'pending',
        ]);

        return redirect()->route('overtimes.index')->with('success', 'Pengajuan lembur berhasil dikirim!');
    }

    public function approve(Request $request, OvertimeRequest $overtime)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'total_pay' => 'nullable|numeric|min:0',
        ]);

        $updateData = [
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes,
        ];

        if ($request->filled('total_pay')) {
            $updateData['total_pay'] = $request->total_pay;
        }

        $overtime->update($updateData);

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
