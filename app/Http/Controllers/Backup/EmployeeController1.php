<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) abort(403);

        $search = $request->get('search', '');
        $department = $request->get('department', 'all');

        $query = User::where('role', 'karyawan');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($department !== 'all') {
            $query->where('department', $department);
        }

        $employees = $query->latest()->paginate(15);

        $departments = User::where('role', 'karyawan')
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        return Inertia::render('Employee/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => ['search' => $search, 'department' => $department],
        ]);
    }

    public function create()
    {
        return Inertia::render('Employee/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Rules\Password::defaults()],
            'employee_id' => 'required|string|unique:users',
            'department' => 'required|string',
            'position' => 'required|string',
            'base_salary' => 'required|numeric|min:0',
            'position_allowance' => 'nullable|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'join_date' => 'required|date',
            'npwp' => 'nullable|string',
            'bpjs_kesehatan' => 'nullable|string',
            'bpjs_ketenagakerjaan' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'karyawan';

        User::create($validated);

        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil ditambahkan!');
    }

    public function edit(User $employee)
    {
        return Inertia::render('Employee/Edit', [
            'employee' => $employee,
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $employee->id,
            'employee_id' => 'required|string|unique:users,employee_id,' . $employee->id,
            'department' => 'required|string',
            'position' => 'required|string',
            'base_salary' => 'required|numeric|min:0',
            'position_allowance' => 'nullable|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'join_date' => 'required|date',
            'npwp' => 'nullable|string',
            'bpjs_kesehatan' => 'nullable|string',
            'bpjs_ketenagakerjaan' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => Rules\Password::defaults()]);
            $validated['password'] = Hash::make($request->password);
        }

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui!');
    }
}
