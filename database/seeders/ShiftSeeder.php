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
            // PAGI (3 shifts)
            [
                'name' => 'Pagi 1',
                'start_time' => '06:00:00',
                'end_time' => '13:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Pagi 1: 06:00 - 13:00',
            ],
            [
                'name' => 'Pagi 2',
                'start_time' => '07:00:00',
                'end_time' => '14:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Pagi 2: 07:00 - 14:00',
            ],
            [
                'name' => 'Pagi 3',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Pagi 3: 08:00 - 16:00',
            ],
            // MIDDLE (4 shifts)
            [
                'name' => 'Middle 1',
                'start_time' => '09:00:00',
                'end_time' => '16:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Middle 1: 09:00 - 16:00',
            ],
            [
                'name' => 'Middle 2',
                'start_time' => '10:00:00',
                'end_time' => '17:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Middle 2: 10:00 - 17:00',
            ],
            [
                'name' => 'Middle 3',
                'start_time' => '11:00:00',
                'end_time' => '18:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Middle 3: 11:00 - 18:00',
            ],
            [
                'name' => 'Middle 4',
                'start_time' => '12:00:00',
                'end_time' => '19:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Middle 4: 12:00 - 19:00',
            ],
            // SIANG (2 shifts)
            [
                'name' => 'Siang 1',
                'start_time' => '13:00:00',
                'end_time' => '20:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Siang 1: 13:00 - 20:00',
            ],
            [
                'name' => 'Siang 2',
                'start_time' => '14:00:00',
                'end_time' => '21:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Siang 2: 14:00 - 21:00',
            ],
            // MALAM (2 shifts)
            [
                'name' => 'Malam 1',
                'start_time' => '20:00:00',
                'end_time' => '06:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => true,
                'is_active' => true,
                'description' => 'Malam 1: 20:00 - 06:00 (melewati tengah malam)',
            ],
            [
                'name' => 'Malam 2',
                'start_time' => '21:00:00',
                'end_time' => '07:00:00',
                'late_tolerance' => 15,
                'is_night_shift' => true,
                'is_active' => true,
                'description' => 'Malam 2: 21:00 - 07:00 (melewati tengah malam)',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }
    }
}
