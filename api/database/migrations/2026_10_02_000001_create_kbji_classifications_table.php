<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Master Klasifikasi Baku Jabatan Indonesia (KBJI 2020)
     */
    public function up(): void
    {
        Schema::create('kbji_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();      // misal: '2', '25', '251', '2512', '2512.01'
            $table->string('title', 200);              // Nama Golongan / Jabatan
            $table->enum('level', [
                'major_group',      // 1 Digit: Golongan Pokok
                'sub_major_group',  // 2 Digit: Golongan Menengah
                'minor_group',      // 3 Digit: Golongan
                'unit_group',       // 4 Digit: Sub-Golongan
                'occupation',       // 5-7 Digit: Jabatan Spesifik
            ])->index();
            $table->string('parent_code', 20)->nullable()->index(); // Menghubungkan hierarki pohon KBJI
            $table->text('description')->nullable();   // Deskripsi tugas umum standar Kemnaker & BPS
            $table->string('isco_code', 20)->nullable(); // Padanan ISCO-08 internasional
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kbji_classifications');
    }
};
