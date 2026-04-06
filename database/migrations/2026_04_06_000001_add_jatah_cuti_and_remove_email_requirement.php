<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add jatah_cuti (leave allowance) column
            if (!Schema::hasColumn('users', 'jatah_cuti')) {
                $table->integer('jatah_cuti')->default(12)->after('status');
            }

            // Make email nullable (no longer required)
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change();
            }
        });

        // Set default jatah_cuti for all existing employees
        \App\Models\User::where('role', 'karyawan')->whereNull('jatah_cuti')->update(['jatah_cuti' => 12]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'jatah_cuti')) {
                $table->dropColumn('jatah_cuti');
            }
        });
    }
};
