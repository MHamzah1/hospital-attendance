<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('job_position_id')
                ->nullable()
                ->after('position')
                ->constrained('job_positions')
                ->nullOnDelete();
        });

        $now = now();
        $positions = DB::table('users')
            ->select('position')
            ->whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->pluck('position');

        foreach ($positions as $positionName) {
            $existingId = DB::table('job_positions')->where('name', $positionName)->value('id');

            if (!$existingId) {
                $existingId = DB::table('job_positions')->insertGetId([
                    'name' => $positionName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('users')
                ->where('position', $positionName)
                ->update(['job_position_id' => $existingId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['job_position_id']);
            $table->dropColumn('job_position_id');
        });
    }
};
