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
        Schema::table('req_pk_loker', function (Blueprint $table) {
            // Menambahkan kolom setelah kolom 'id' (sesuaikan jika id tidak ada atau berbeda namanya)
            $table->unsignedBigInteger('kbji_2026_id')->nullable();
            $table->boolean('is_mapped')->default(false);

            $table->foreign('kbji_2026_id')->references('id')->on('kbji_classifications_2026')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('req_pk_loker', function (Blueprint $table) {
            $table->dropForeign(['kbji_2026_id']);
            $table->dropColumn(['kbji_2026_id', 'is_mapped']);
        });
    }
};
