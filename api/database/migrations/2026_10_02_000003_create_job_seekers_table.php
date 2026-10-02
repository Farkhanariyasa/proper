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
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->id();
            $table->char('nik', 16)->unique();
            $table->string('full_name', 150);
            $table->string('phone', 20);
            $table->date('birth_date');
            $table->enum('gender', ['L', 'P']);
            $table->char('regency_id', 5);
            $table->foreignId('education_level_id')->constrained('education_levels');
            $table->string('study_field_group', 100)->index();
            $table->string('study_field_detail', 150)->nullable();
            $table->enum('experience_range', ['fresh_graduate', '<1', '1-3', '3-5', '>5']);
            $table->string('desired_occupation', 150)->nullable();
            $table->json('trainings')->nullable();
            $table->json('certifications')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('regency_id')
                ->references('id')
                ->on('regencies')
                ->cascadeOnDelete();
            
            $table->index('regency_id');
        });

        Schema::create('job_seeker_skills', function (Blueprint $table) {
            $table->foreignId('job_seeker_id')
                ->constrained('job_seekers')
                ->cascadeOnDelete();

            $table->integer('esco_skill_id');
            $table->foreign('esco_skill_id')
                ->references('id')
                ->on('skill_nodes')
                ->cascadeOnDelete();

            $table->primary(['job_seeker_id', 'esco_skill_id']);
            $table->index('esco_skill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_seeker_skills');
        Schema::dropIfExists('job_seekers');
    }
};
