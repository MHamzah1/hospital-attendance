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
            // Add missing allowances
            if (!Schema::hasColumn('users', 'functional_allowance')) {
                $table->decimal('functional_allowance', 15, 2)->default(0)->after('position_allowance');
            }
            if (!Schema::hasColumn('users', 'special_allowance')) {
                $table->decimal('special_allowance', 15, 2)->default(0)->after('functional_allowance');
            }
            if (!Schema::hasColumn('users', 'attendance_allowance')) {
                $table->decimal('attendance_allowance', 15, 2)->default(0)->after('transport_allowance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'functional_allowance')) {
                $table->dropColumn('functional_allowance');
            }
            if (Schema::hasColumn('users', 'special_allowance')) {
                $table->dropColumn('special_allowance');
            }
            if (Schema::hasColumn('users', 'attendance_allowance')) {
                $table->dropColumn('attendance_allowance');
            }
        });
    }
};
