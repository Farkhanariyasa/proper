<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->foreignId('kbji_id')
                ->nullable()
                ->after('desired_occupation')
                ->constrained('kbji_classifications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->dropForeign(['kbji_id']);
            $table->dropColumn('kbji_id');
        });
    }
};
