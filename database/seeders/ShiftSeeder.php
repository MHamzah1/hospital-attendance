<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Shift Pagi',
                'start_time' => '07:00:00',
                'end_time' => '14:00:00',
                'late_tolerance' => 15, // Toleransi 15 menit
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Shift pagi untuk karyawan rumah sakit, jam kerja 07:00 - 14:00',
            ],
            [
                'name' => 'Shift Siang',
                'start_time' => '14:00:00',
                'end_time' => '21:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Shift siang untuk karyawan rumah sakit, jam kerja 14:00 - 21:00',
            ],
            [
                'name' => 'Shift Malam',
                'start_time' => '21:00:00',
                'end_time' => '07:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => true,
                'is_active' => true,
                'description' => 'Shift malam untuk karyawan rumah sakit, jam kerja 21:00 - 07:00 (melewati tengah malam)',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }
    }
}
