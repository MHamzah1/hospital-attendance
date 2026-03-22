<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // TUNJANGAN (ALLOWANCES) - Additional columns
            $table->decimal('functional_allowance', 15, 2)->default(0)->after('position_allowance');
            $table->decimal('special_allowance', 15, 2)->default(0)->after('functional_allowance');
            $table->decimal('attendance_allowance', 15, 2)->default(0)->after('transport_allowance');

            // Salary Correction
            $table->decimal('salary_correction', 15, 2)->default(0)->after('other_allowance');

            // LEMBUR (OVERTIME) - More detailed breakdown
            $table->decimal('overtime_hourly', 15, 2)->default(0)->after('overtime_hours');
            $table->decimal('overtime_shift', 15, 2)->default(0)->after('overtime_hourly');
            $table->decimal('overtime_on_call', 15, 2)->default(0)->after('overtime_shift');
            $table->decimal('overtime_mod', 15, 2)->default(0)->after('overtime_on_call');
            $table->decimal('overtime_holiday', 15, 2)->default(0)->after('overtime_mod');

            // Total allowances & overtime (calculated)
            $table->decimal('total_allowance', 15, 2)->default(0)->after('salary_correction');
            $table->decimal('total_overtime_other', 15, 2)->default(0)->after('overtime_holiday');

            // POTONGAN (DEDUCTIONS) - Additional columns
            $table->decimal('cdt_deduction', 15, 2)->default(0)->after('bpjs_kesehatan');
            $table->decimal('alpha_deduction', 15, 2)->default(0)->after('cdt_deduction');
            $table->decimal('cashbond_deduction', 15, 2)->default(0)->after('alpha_deduction');
            $table->decimal('piutang_obat_deduction', 15, 2)->default(0)->after('cashbond_deduction');
            $table->decimal('bpjs_pensiun_jp', 15, 2)->default(0)->after('bpjs_pensiun');
            $table->decimal('salary_correction_deduction', 15, 2)->default(0)->after('pph21');
            $table->decimal('bank_admin_deduction', 15, 2)->default(0)->after('salary_correction_deduction');

            // Reorder deductions and update old absence_deduction to map to alpha
            // Note: We'll handle the deprecation in the model
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'functional_allowance',
                'special_allowance',
                'attendance_allowance',
                'salary_correction',
                'overtime_hourly',
                'overtime_shift',
                'overtime_on_call',
                'overtime_mod',
                'overtime_holiday',
                'total_allowance',
                'total_overtime_other',
                'cdt_deduction',
                'alpha_deduction',
                'cashbond_deduction',
                'piutang_obat_deduction',
                'bpjs_pensiun_jp',
                'salary_correction_deduction',
                'bank_admin_deduction',
            ]);
        });
    }
};
