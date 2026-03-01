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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama shift (Shift Pagi, Shift Siang, Shift Malam)
            $table->time('start_time'); // Jam masuk (07:00, 14:00, 21:00)
            $table->time('end_time'); // Jam keluar (14:00, 21:00, 07:00)
            $table->integer('late_tolerance')->default(15); // Toleransi keterlambatan dalam menit
            $table->boolean('is_night_shift')->default(false); // Shift malam (melewati tengah malam)
            $table->boolean('is_active')->default(true); // Status aktif
            $table->text('description')->nullable(); // Deskripsi shift
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
