<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('department')->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('unit')->constrained()->nullOnDelete();
        });

        // Expand approval_role enum to include 'direktur'
        DB::statement("ALTER TABLE users MODIFY COLUMN approval_role ENUM('staf','koordinator','manajer','direktur') DEFAULT 'staf'");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['department_id', 'unit_id']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN approval_role ENUM('staf','koordinator','manajer') DEFAULT 'staf'");
    }
};
