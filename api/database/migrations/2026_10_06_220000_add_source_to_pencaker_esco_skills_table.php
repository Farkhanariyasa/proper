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
        Schema::table('pencaker_esco_skills', function (Blueprint $table) {
            if (!Schema::hasColumn('pencaker_esco_skills', 'source')) {
                $table->string('source', 50)->nullable()->default('keahlian')->after('is_manual');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pencaker_esco_skills', function (Blueprint $table) {
            if (Schema::hasColumn('pencaker_esco_skills', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
