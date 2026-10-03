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
        Schema::create('lowongan_skills', function (Blueprint $table) {
            $table->uuid('lowongan_id');
            $table->integer('esco_skill_id');
            $table->enum('tipe_keahlian', ['wajib', 'tambahan'])->default('wajib');
            $table->enum('level_kemahiran', ['pemula', 'menengah', 'ahli'])->default('menengah');

            $table->foreign('lowongan_id')
                ->references('id')
                ->on('lowongan_kerja')
                ->cascadeOnDelete();

            $table->foreign('esco_skill_id')
                ->references('id')
                ->on('skill_nodes')
                ->cascadeOnDelete();

            $table->primary(['lowongan_id', 'esco_skill_id']);
            $table->index('esco_skill_id');
            $table->index(['lowongan_id', 'tipe_keahlian']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lowongan_skills');
    }
};
