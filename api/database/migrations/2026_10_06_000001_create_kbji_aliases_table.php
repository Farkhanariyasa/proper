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
        Schema::create('kbji_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('raw_term')->unique()->comment('Judul pekerjaan mentah dari req_pk_loker');
            $table->foreignId('kbji_id')->nullable()->constrained('kbji_classifications_2026')->nullOnDelete();
            $table->string('method', 50)->nullable()->comment('exact, fuzzy, llm, manual');
            $table->float('confidence')->nullable()->comment('Tingkat keyakinan mapping (0.0 - 1.0)');
            $table->string('status', 20)->default('pending')->comment('pending, verified, rejected');
            $table->integer('frequency')->default(0)->comment('Berapa kali judul ini muncul');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kbji_aliases');
    }
};
