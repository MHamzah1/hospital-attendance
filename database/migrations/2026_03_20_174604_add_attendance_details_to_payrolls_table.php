<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Attendance details
            $table->integer('late_minutes')->default(0)->after('late_days'); // Store late time in seconds

            // Overtime details by category
            $table->integer('overtime_jam_seconds')->default(0)->after('overtime_hours'); // Lembur jam in seconds
            $table->integer('overtime_malam_count')->default(0)->after('overtime_jam_seconds'); // Lembur malam count
            $table->integer('overtime_shift_count')->default(0)->after('overtime_malam_count'); // Lembur shift count
            $table->integer('overtime_on_call_count')->default(0)->after('overtime_shift_count'); // Lembur on call count
            $table->integer('overtime_hari_raya_count')->default(0)->after('overtime_on_call_count'); // Lembur hari raya count
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'late_minutes',
                'overtime_jam_seconds',
                'overtime_malam_count',
                'overtime_shift_count',
                'overtime_on_call_count',
                'overtime_hari_raya_count'
            ]);
        });
    }
};
