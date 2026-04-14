<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class DepartmentUnitSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'DIREKSI' => ['KANTOR'],
            'MANAJEMEN' => ['KANTOR'],
            'PENUNJANG MEDIS' => [
                'FISIOTERAPI', 'FARMASI', 'LABORATORIUM', 'RADIOLOGI',
                'LOGISTIK MEDIS', 'REKAM MEDIS', 'GIZI', 'CASEMIX',
            ],
            'KEPERAWATAN' => [
                'POLIKLINIK', 'IGD', 'ICU', 'KEBIDANAN', 'OK', 'RAWAT INAP',
            ],
            'NON MEDIS' => [
                'IT', 'ADMIN & KASIR', 'SDM', 'MARKETING', 'CUSTOMER CARE',
                'UMUM', 'KESLING', 'K3RS', 'KANTOR',
            ],
            'MEDIS' => ['DOKTER'],
        ];

        foreach ($data as $deptName => $units) {
            $dept = Department::firstOrCreate(['name' => $deptName]);

            foreach ($units as $unitName) {
                Unit::firstOrCreate([
                    'department_id' => $dept->id,
                    'name' => $unitName,
                ]);
            }
        }
    }
}
