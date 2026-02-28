<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin SDM
        User::create([
            'name' => 'Admin SDM',
            'email' => 'admin@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'admin_sdm',
            'employee_id' => 'ADM-001',
            'department' => 'SDM & Personalia',
            'position' => 'Admin SDM',
            'base_salary' => 8000000,
            'position_allowance' => 2000000,
            'meal_allowance' => 50000,
            'transport_allowance' => 30000,
            'phone' => '081234567890',
            'join_date' => '2020-01-01',
            'status' => 'active',
        ]);

        // Karyawan - Perawat
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'employee_id' => 'NRS-001',
            'department' => 'Rawat Inap',
            'position' => 'Perawat',
            'base_salary' => 5500000,
            'position_allowance' => 1000000,
            'meal_allowance' => 40000,
            'transport_allowance' => 25000,
            'phone' => '081234567891',
            'join_date' => '2021-03-15',
            'npwp' => '12.345.678.9-012.000',
            'bpjs_kesehatan' => 'BK-0001234567',
            'bpjs_ketenagakerjaan' => 'BT-0001234567',
            'status' => 'active',
        ]);

        // Karyawan - Perawat 2
        User::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'employee_id' => 'NRS-002',
            'department' => 'IGD',
            'position' => 'Perawat Senior',
            'base_salary' => 6500000,
            'position_allowance' => 1500000,
            'meal_allowance' => 45000,
            'transport_allowance' => 30000,
            'phone' => '081234567892',
            'join_date' => '2019-08-01',
            'npwp' => '12.345.678.9-013.000',
            'bpjs_kesehatan' => 'BK-0001234568',
            'bpjs_ketenagakerjaan' => 'BT-0001234568',
            'status' => 'active',
        ]);

        // Karyawan - Dokter
        User::create([
            'name' => 'Dr. Ahmad Wijaya',
            'email' => 'dokter@hospital.com',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'employee_id' => 'DOC-001',
            'department' => 'Poliklinik Umum',
            'position' => 'Dokter Umum',
            'base_salary' => 15000000,
            'position_allowance' => 5000000,
            'meal_allowance' => 60000,
            'transport_allowance' => 50000,
            'phone' => '081234567893',
            'join_date' => '2018-01-10',
            'npwp' => '12.345.678.9-014.000',
            'bpjs_kesehatan' => 'BK-0001234569',
            'bpjs_ketenagakerjaan' => 'BT-0001234569',
            'status' => 'active',
        ]);

        // Additional employees
        $employees = [
            ['name' => 'Dewi Lestari', 'eid' => 'PHA-001', 'dept' => 'Farmasi', 'pos' => 'Apoteker', 'salary' => 7000000, 'pa' => 1500000],
            ['name' => 'Rudi Hermawan', 'eid' => 'LAB-001', 'dept' => 'Laboratorium', 'pos' => 'Analis Lab', 'salary' => 5000000, 'pa' => 1000000],
            ['name' => 'Ani Kusuma', 'eid' => 'ADM-002', 'dept' => 'Administrasi', 'pos' => 'Staff Administrasi', 'salary' => 4500000, 'pa' => 800000],
            ['name' => 'Eko Prasetyo', 'eid' => 'RAD-001', 'dept' => 'Radiologi', 'pos' => 'Radiografer', 'salary' => 6000000, 'pa' => 1200000],
        ];

        foreach ($employees as $i => $emp) {
            User::create([
                'name' => $emp['name'],
                'email' => strtolower(explode(' ', $emp['name'])[0]) . '@hospital.com',
                'password' => Hash::make('password'),
                'role' => 'karyawan',
                'employee_id' => $emp['eid'],
                'department' => $emp['dept'],
                'position' => $emp['pos'],
                'base_salary' => $emp['salary'],
                'position_allowance' => $emp['pa'],
                'meal_allowance' => 40000,
                'transport_allowance' => 25000,
                'phone' => '08123456789' . ($i + 4),
                'join_date' => '2022-0' . ($i + 1) . '-01',
                'npwp' => '12.345.678.9-01' . ($i + 5) . '.000',
                'bpjs_kesehatan' => 'BK-000123456' . ($i + 10),
                'bpjs_ketenagakerjaan' => 'BT-000123456' . ($i + 10),
                'status' => 'active',
            ]);
        }
    }
}
