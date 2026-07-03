<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) abort(403);

        $search     = $request->get('search', '');
        $department = $request->get('department', 'all');
        $unit       = $request->get('unit', 'all');

        $query = User::with('departmentModel', 'unitModel', 'jobPosition')->where('role', 'karyawan');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhereHas('departmentModel', fn($d) => $d->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('unitModel', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('jobPosition', fn($j) => $j->where('name', 'like', "%{$search}%"));
            });
        }

        if ($department !== 'all') {
            $query->where('department_id', $department);
        }

        if ($unit !== 'all') {
            $query->where('unit_id', $unit);
        }

        $employees = $query->latest()->paginate(15);
        $employees->getCollection()->transform(function ($employee) {
            $employee->photo_url = $employee->photo ? '/storage/' . $employee->photo : null;
            return $employee;
        });

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $units = Unit::orderBy('name')->get(['id', 'name', 'department_id']);
        $jobPositions = JobPosition::orderBy('name')->get(['id', 'name']);

        $activeCount   = User::where('role', 'karyawan')->where('status', 'active')->count();
        $inactiveCount = User::where('role', 'karyawan')->where('status', '!=', 'active')->count();

        return Inertia::render('Employee/Index', [
            'employees'     => $employees,
            'departments'   => $departments,
            'units'         => $units,
            'jobPositions'  => $jobPositions,
            'filters'       => ['search' => $search, 'department' => $department, 'unit' => $unit],
            'activeCount'   => $activeCount,
            'inactiveCount' => $inactiveCount,
        ]);
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $units = Unit::orderBy('name')->get(['id', 'name', 'department_id']);
        $jobPositions = JobPosition::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Employee/Create', [
            'departments' => $departments,
            'units' => $units,
            'jobPositions' => $jobPositions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Informasi Dasar
            'nip'                     => 'required|string|unique:users',
            'name'                    => 'required|string|max:255',
            'password'                => ['required', Rules\Password::defaults()],
            'gender'                  => 'nullable|string|in:L,P',
            'education'               => 'nullable|string',
            'birth_place'             => 'nullable|string',
            'birth_date'              => 'nullable|date',
            
            // Kontak & Pekerjaan
            'phone'                   => 'nullable|string',
            'address'                 => 'nullable|string',
            'city'                    => 'nullable|string',
            'department'              => 'nullable|string',
            'department_id'           => 'required|exists:departments,id',
            'unit_id'                 => ['nullable', Rule::exists('units', 'id')->where(function ($q) use ($request) {
                if (!$request->department_id) {
                    return $q;
                }
                return $q->where('department_id', $request->department_id);
            })],
            'unit'                    => 'nullable|string',
            'position'                => 'nullable|string|required_without:job_position_id',
            'job_position_id'         => 'nullable|exists:job_positions,id|required_without:position',
            'approval_role'           => 'nullable|in:staf,koordinator,manajer,direktur',
            'join_date'               => 'required|date',
            'status'                  => 'nullable|in:active,inactive',
            'jatah_cuti'              => 'nullable|integer|min:0',
            
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
        $validated['jatah_cuti'] = $validated['jatah_cuti'] ?? 12;
        $validated['approval_role'] = $validated['approval_role'] ?? 'staf';
        $this->syncDepartmentUnitFields($validated);
        $this->syncJobPositionFields($validated);

        User::create($validated);

        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil ditambahkan!');
    }

    public function edit(User $employee)
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $units = Unit::orderBy('name')->get(['id', 'name', 'department_id']);
        $jobPositions = JobPosition::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Employee/Edit', [
            'employee' => $employee->load('departmentModel', 'unitModel', 'jobPosition'),
            'departments' => $departments,
            'units' => $units,
            'jobPositions' => $jobPositions,
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $validated = $request->validate([
            // Informasi Dasar
            'nip'                  => 'required|string|unique:users,nip,' . $employee->id,
            'name'                 => 'required|string|max:255',
            'password'             => ['nullable', Rules\Password::defaults()],
            'gender'               => 'nullable|string|in:L,P',
            'education'            => 'nullable|string',
            'birth_place'          => 'nullable|string',
            'birth_date'           => 'nullable|date',
            
            // Kontak & Pekerjaan
            'phone'                => 'nullable|string',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string',
            'department'           => 'nullable|string',
            'department_id'        => 'required|exists:departments,id',
            'unit_id'              => ['nullable', Rule::exists('units', 'id')->where(function ($q) use ($request) {
                if (!$request->department_id) {
                    return $q;
                }
                return $q->where('department_id', $request->department_id);
            })],
            'unit'                 => 'nullable|string',
            'position'             => 'nullable|string|required_without:job_position_id',
            'job_position_id'      => 'nullable|exists:job_positions,id|required_without:position',
            'approval_role'        => 'nullable|in:staf,koordinator,manajer,direktur',
            'join_date'            => 'required|date',
            'status'               => 'required|in:active,inactive',
            'jatah_cuti'           => 'nullable|integer|min:0',
            
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
        } else {
            unset($validated['password']);
        }

        $this->syncDepartmentUnitFields($validated);
        $this->syncJobPositionFields($validated);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui!');
    }

    private function syncDepartmentUnitFields(array &$validated): void
    {
        $department = Department::find($validated['department_id']);
        if (!$department) {
            throw ValidationException::withMessages(['department_id' => 'Departemen tidak valid.']);
        }

        $validated['department'] = $department->name;

        if (!empty($validated['unit_id'])) {
            $unit = Unit::find($validated['unit_id']);

            if (!$unit || (int) $unit->department_id !== (int) $department->id) {
                throw ValidationException::withMessages(['unit_id' => 'Unit tidak sesuai dengan departemen yang dipilih.']);
            }

            $validated['unit'] = $unit->name;
        } else {
            $validated['unit'] = null;
        }
    }

    private function syncJobPositionFields(array &$validated): void
    {
        if (!empty($validated['job_position_id'])) {
            $jobPosition = JobPosition::find($validated['job_position_id']);

            if (!$jobPosition) {
                throw ValidationException::withMessages(['job_position_id' => 'Jenis jabatan tidak valid.']);
            }

            $validated['position'] = $jobPosition->name;
            return;
        }

        $positionName = isset($validated['position']) ? trim((string) $validated['position']) : '';
        if ($positionName === '') {
            throw ValidationException::withMessages(['position' => 'Jabatan wajib diisi.']);
        }

        $jobPosition = JobPosition::firstOrCreate(['name' => $positionName]);
        $validated['job_position_id'] = $jobPosition->id;
        $validated['position'] = $jobPosition->name;
    }
}
