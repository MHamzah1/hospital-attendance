<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Shift;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if Libur shift already exists, if not add it
        if (!Shift::where('name', 'Libur')->exists()) {
            Shift::create([
                'name' => 'Libur',
                'start_time' => '00:00:00',
                'end_time' => '23:59:00',
                'late_tolerance' => 0,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Libur: Karyawan tidak ada jadwal kerja',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Shift::where('name', 'Libur')->delete();
    }
};
