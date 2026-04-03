<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add salary columns if they don't exist
            if (!Schema::hasColumn('users', 'base_salary')) {
                $table->decimal('base_salary', 15, 2)->default(0)->after('position');
            }
            if (!Schema::hasColumn('users', 'position_allowance')) {
                $table->decimal('position_allowance', 15, 2)->default(0)->after('base_salary');
            }
            if (!Schema::hasColumn('users', 'meal_allowance')) {
                $table->decimal('meal_allowance', 15, 2)->default(0)->after('position_allowance');
            }
            if (!Schema::hasColumn('users', 'transport_allowance')) {
                $table->decimal('transport_allowance', 15, 2)->default(0)->after('meal_allowance');
            }
            
            // Add other details if they don't exist
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'education')) {
                $table->string('education')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('education');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('birth_place');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('npwp');
            }
            if (!Schema::hasColumn('users', 'bank_account')) {
                $table->string('bank_account')->nullable()->after('bank_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['base_salary', 'position_allowance', 'meal_allowance', 'transport_allowance',
                       'gender', 'education', 'birth_place', 'birth_date', 'city', 'bank_name', 'bank_account'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
