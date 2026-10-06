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
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->string('province_id', 2)->nullable();
            $table->string('regency_id', 4)->nullable();
            $table->foreignId('education_level_id')->nullable()->constrained('education_levels')->nullOnDelete();
            
            // Note: Provinces and Regencies primary keys are char/string.
            $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();
            $table->foreign('regency_id')->references('id')->on('regencies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['education_level_id']);
            
            $table->dropColumn(['province_id', 'regency_id', 'education_level_id']);
        });
    }
};
