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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('leave_allowance')->default(12)->after('status'); // Jatah cuti per tahun
            $table->integer('leave_used')->default(0)->after('leave_allowance'); // Cuti yang sudah digunakan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['leave_allowance', 'leave_used']);
        });
    }
};
