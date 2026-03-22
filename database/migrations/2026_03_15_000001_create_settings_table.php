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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->string('group')->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default overtime rate setting
        DB::table('settings')->insert([
            ['key' => 'overtime_rate_per_hour', 'value' => '10000', 'type' => 'number', 'group' => 'overtime', 'description' => 'Tarif lembur per jam (Rp)', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'overtime_rate_night', 'value' => '15000', 'type' => 'number', 'group' => 'overtime', 'description' => 'Tarif lembur malam per jam (Rp)', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'overtime_rate_holiday', 'value' => '20000', 'type' => 'number', 'group' => 'overtime', 'description' => 'Tarif lembur hari libur per jam (Rp)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
