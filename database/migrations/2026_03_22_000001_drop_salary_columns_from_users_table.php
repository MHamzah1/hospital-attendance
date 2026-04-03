<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if columns don't exist
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropColumn([
                    'base_salary',
                    'position_allowance',
                    'meal_allowance',
                    'transport_allowance',
                ]);
            } catch (\Exception $e) {
                // Columns don't exist, skip
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('base_salary', 15, 2)->default(0)->after('position');
            $table->decimal('position_allowance', 15, 2)->default(0)->after('base_salary');
            $table->decimal('meal_allowance', 15, 2)->default(0)->after('position_allowance');
            $table->decimal('transport_allowance', 15, 2)->default(0)->after('meal_allowance');
        });
    }
};
