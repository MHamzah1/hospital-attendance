<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Tambah field Lembur Malam (overtime_night) setelah overtime_shift
            if (!Schema::hasColumn('payrolls', 'overtime_night')) {
                $table->decimal('overtime_night', 15, 2)->default(0)->after('overtime_shift');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'overtime_night')) {
                $table->dropColumn('overtime_night');
            }
        });
    }
};
