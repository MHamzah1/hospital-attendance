<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        $jobPositions = JobPosition::withCount('users')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Department/Index', [
            'departments' => $departments,
            'managers' => $managers,
            'jobPositions' => $jobPositions,
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);

        Department::create([
            'name' => trim($validated['name']),
        ]);

        return back()->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Departemen tidak bisa dihapus karena masih dipakai data karyawan.');
        }

        if ($department->units()->exists()) {
            return back()->with('error', 'Departemen tidak bisa dihapus karena masih memiliki unit.');
        }

        $department->delete();

        return back()->with('success', 'Departemen berhasil dihapus.');
    }

    public function storeUnit(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'name')->where(function ($query) use ($request) {
                    return $query->where('department_id', $request->department_id);
                }),
            ],
        ]);

        Unit::create([
            'department_id' => $validated['department_id'],
            'name' => trim($validated['name']),
        ]);

        return back()->with('success', 'Unit berhasil ditambahkan.');
    }

    public function destroyUnit(Unit $unit)
    {
        if ($unit->users()->exists()) {
            return back()->with('error', 'Unit tidak bisa dihapus karena masih dipakai data karyawan.');
        }

        $unit->delete();

        return back()->with('success', 'Unit berhasil dihapus.');
    }

    public function storeJobPosition(Request $request)
    {
        $normalizedName = $this->normalizeJobPositionName(
            $this->normalizeName($request->input('name'))
        );

        $validated = validator([
            'name' => $normalizedName,
        ], [
            'name' => ['required', 'string', 'max:255', 'unique:job_positions,name'],
        ])->validate();

        JobPosition::create([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Jenis jabatan berhasil ditambahkan.');
    }

    public function destroyJobPosition(JobPosition $jobPosition)
    {
        if ($jobPosition->users()->exists()) {
            return back()->with('error', 'Jenis jabatan tidak bisa dihapus karena masih dipakai data karyawan.');
        }

        $jobPosition->delete();

        return back()->with('success', 'Jenis jabatan berhasil dihapus.');
    }

    public function syncFromExcel()
    {
        $filePath = base_path('DATA PEGAWAI/DEPARTEMEN.xlsx');

        if (!file_exists($filePath)) {
            return back()->with('error', 'File DATA PEGAWAI/DEPARTEMEN.xlsx tidak ditemukan.');
        }

        $createdDepartments = 0;
        $createdUnits = 0;
        $createdPositions = 0;

        DB::transaction(function () use ($filePath, &$createdDepartments, &$createdUnits, &$createdPositions) {
            $spreadsheet = IOFactory::load($filePath);

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $rows = $worksheet->toArray(null, true, false, false);
                if (empty($rows)) {
                    continue;
                }

                $headerIndex = null;
                foreach ($rows as $index => $row) {
                    if (!empty(array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== ''))) {
                        $headerIndex = $index;
                        break;
                    }
                }

                if ($headerIndex === null) {
                    continue;
                }

                $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rows[$headerIndex]);
                $departmentCol = $this->findColumn($headers, ['departemen', 'departmen', 'department']);
                $unitCol = $this->findColumn($headers, ['unit', 'unitkerja']);
                $positionCol = $this->findColumn($headers, ['jabatan', 'position', 'posisi']);

                foreach (array_slice($rows, $headerIndex + 1) as $row) {
                    $departmentName = $departmentCol !== null ? $this->normalizeDepartmentName($this->normalizeName($row[$departmentCol] ?? null)) : null;
                    $unitName = $unitCol !== null ? $this->normalizeName($row[$unitCol] ?? null) : null;
                    $positionName = $positionCol !== null
                        ? $this->normalizeJobPositionName($this->normalizeName($row[$positionCol] ?? null))
                        : null;

                    if (!$departmentName && !$unitName && !$positionName) {
                        continue;
                    }

                    $department = null;
                    if ($departmentName) {
                        $department = Department::firstOrCreate(['name' => $departmentName]);
                        if ($department->wasRecentlyCreated) {
                            $createdDepartments++;
                        }
                    }

                    if ($department && $unitName) {
                        $unit = Unit::firstOrCreate([
                            'department_id' => $department->id,
                            'name' => $unitName,
                        ]);

                        if ($unit->wasRecentlyCreated) {
                            $createdUnits++;
                        }
                    }

                    if ($positionName) {
                        $jobPosition = JobPosition::firstOrCreate(['name' => $positionName]);
                        if ($jobPosition->wasRecentlyCreated) {
                            $createdPositions++;
                        }
                    }
                }
            }
        });

        return back()->with(
            'success',
            "Sinkron master dari DEPARTEMEN.xlsx selesai. Tambahan baru: {$createdDepartments} departemen, {$createdUnits} unit, {$createdPositions} jenis jabatan."
        );
    }

    public function updateManager(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $unit->update(['manager_id' => $validated['manager_id']]);

        return back()->with('success', "Manager untuk unit {$unit->name} berhasil diperbarui.");
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/', '', trim($value)));
    }

    private function findColumn(array $headers, array $aliases): ?int
    {
        foreach ($headers as $index => $header) {
            if (in_array($header, $aliases, true)) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeName($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $name = trim((string) $value);
        $name = preg_replace('/\s+/', ' ', $name);

        return $name === '' ? null : $name;
    }

    private function normalizeDepartmentName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $upperName = strtoupper($name);

        // Samakan typo umum dari file DEPARTEMEN.xlsx
        if ($upperName === 'MANAGEMEN') {
            return 'MANAJEMEN';
        }

        return $upperName;
    }

    private function normalizeJobPositionName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        return strtoupper($name);
    }
}
