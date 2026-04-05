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

        $search     = $request->get('search', '');
        $department = $request->get('department', 'all');

        $query = User::where('role', 'karyawan');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")           // BUG FIX: tambah NIP
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

        $activeCount   = User::where('role', 'karyawan')->where('status', 'active')->count();
        $inactiveCount = User::where('role', 'karyawan')->where('status', '!=', 'active')->count();

        return Inertia::render('Employee/Index', [
            'employees'     => $employees,
            'departments'   => $departments,
            'filters'       => ['search' => $search, 'department' => $department],
            'activeCount'   => $activeCount,
            'inactiveCount' => $inactiveCount,
        ]);
    }

    public function create()
    {
        return Inertia::render('Employee/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Informasi Dasar
            'nip'                     => 'required|string|unique:users',
            'name'                    => 'required|string|max:255',
            'email'                   => 'required|string|email|max:255|unique:users',
            'password'                => ['required', Rules\Password::defaults()],
            'gender'                  => 'nullable|string|in:L,P',
            'education'               => 'nullable|string',
            'birth_place'             => 'nullable|string',
            'birth_date'              => 'nullable|date',
            
            // Kontak & Pekerjaan
            'phone'                   => 'nullable|string',
            'address'                 => 'nullable|string',
            'city'                    => 'nullable|string',
            'department'              => 'required|string',
            'position'                => 'required|string',
            'join_date'               => 'required|date',
            'status'                  => 'nullable|in:active,inactive',
            
            // Komponen Gaji
            'base_salary'             => 'required|numeric|min:0',
            'position_allowance'      => 'nullable|numeric|min:0',
            'functional_allowance'    => 'nullable|numeric|min:0',
            'special_allowance'       => 'nullable|numeric|min:0',
            'meal_allowance'          => 'nullable|numeric|min:0',
            'transport_allowance'     => 'nullable|numeric|min:0',
            'attendance_allowance'    => 'nullable|numeric|min:0',
            
            // Data Bank & Dokumen
            'npwp'                    => 'nullable|string',
            'bpjs_kesehatan'          => 'nullable|string',
            'bpjs_ketenagakerjaan'    => 'nullable|string',
            'bank_name'               => 'nullable|string',
            'bank_account'            => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role']     = 'karyawan';
        $validated['status']   = $validated['status'] ?? 'active';

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
            // Informasi Dasar
            'nip'                  => 'required|string|unique:users,nip,' . $employee->id,
            'name'                 => 'required|string|max:255',
            'email'                => 'required|string|email|max:255|unique:users,email,' . $employee->id,
            'password'             => 'nullable|' . Rules\Password::defaults(),
            'gender'               => 'nullable|string|in:L,P',
            'education'            => 'nullable|string',
            'birth_place'          => 'nullable|string',
            'birth_date'           => 'nullable|date',
            
            // Kontak & Pekerjaan
            'phone'                => 'nullable|string',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string',
            'department'           => 'required|string',
            'position'             => 'required|string',
            'join_date'            => 'required|date',
            'status'               => 'required|in:active,inactive',
            
            // Komponen Gaji
            'base_salary'          => 'required|numeric|min:0',
            'position_allowance'   => 'nullable|numeric|min:0',
            'functional_allowance' => 'nullable|numeric|min:0',
            'special_allowance'    => 'nullable|numeric|min:0',
            'meal_allowance'       => 'nullable|numeric|min:0',
            'transport_allowance'  => 'nullable|numeric|min:0',
            'attendance_allowance' => 'nullable|numeric|min:0',
            
            // Data Bank & Dokumen
            'npwp'                 => 'nullable|string',
            'bpjs_kesehatan'       => 'nullable|string',
            'bpjs_ketenagakerjaan' => 'nullable|string',
            'bank_name'            => 'nullable|string',
            'bank_account'         => 'nullable|string',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => Rules\Password::defaults()]);
            $validated['password'] = Hash::make($request->password);
        }

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui!');
    }
}
