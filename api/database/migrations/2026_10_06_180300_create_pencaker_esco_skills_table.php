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
        Schema::create('pencaker_esco_skills', function (Blueprint $table) {
            $table->id();
            // Assumes req_pk_pencaker has an integer/bigInteger primary key 'id'
            $table->unsignedBigInteger('pencaker_id');
            $table->integer('esco_skill_id');
            
            // Kolom ini bisa berguna untuk menandai apakah hasil ekstrak AI atau di-add manual operator
            $table->boolean('is_manual')->default(false); 
            $table->timestamps();

            // Foreign keys
            $table->foreign('pencaker_id')
                ->references('id')
                ->on('req_pk_pencaker')
                ->cascadeOnDelete();

            $table->foreign('esco_skill_id')
                ->references('id')
                ->on('skill_nodes')
                ->cascadeOnDelete();

            // Prevent duplicate skill for the same pencaker
            $table->unique(['pencaker_id', 'esco_skill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pencaker_esco_skills');
    }
};
