<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['units.manager', 'units' => function ($q) {
            $q->withCount('users');
        }])->withCount('users')->orderBy('name')->get();

        $managers = User::where('approval_role', 'manajer')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'position']);

        return Inertia::render('Department/Index', [
            'departments' => $departments,
            'managers' => $managers,
        ]);
    }

    public function updateManager(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $unit->update(['manager_id' => $validated['manager_id']]);

        return back()->with('success', "Manajer untuk unit {$unit->name} berhasil diperbarui.");
    }
}
