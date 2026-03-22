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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable()->after('employee_id'); // NIP dari admin
            $table->enum('gender', ['L', 'P'])->nullable()->after('name'); // L = Laki-laki, P = Perempuan
            $table->string('education')->nullable()->after('gender'); // Pendidikan
            $table->string('birth_place')->nullable()->after('education'); // Tempat lahir
            $table->date('birth_date')->nullable()->after('birth_place'); // Tanggal lahir
            $table->string('city')->nullable()->after('address'); // Kota
            $table->string('bank_name')->nullable()->after('bpjs_ketenagakerjaan'); // Nama pemilik rekening
            $table->string('bank_account')->nullable()->after('bank_name'); // Nomor rekening
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nip', 'gender', 'education', 'birth_place',
                'birth_date', 'city', 'bank_name', 'bank_account'
            ]);
        });
    }
};
