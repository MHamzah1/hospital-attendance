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
        Schema::create('user_schedule_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_schedule_id')->constrained('user_schedules')->onDelete('cascade');
            $table->foreignId('shift_id_old')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('shift_id_new')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('changed_by_id')->constrained('users')->onDelete('restrict');
            $table->timestamp('changed_at')->useCurrent();

            // Indexes for common queries
            $table->index(['user_schedule_id', 'changed_at']);
            $table->index(['changed_by_id', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_schedule_histories');
    }
};
