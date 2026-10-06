<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // req_pk_pencaker ~920k baris; tanpa index, filter wilayah/pendidikan = full scan
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->index('province_id', 'idx_pencaker_province_id');
            $table->index('regency_id', 'idx_pencaker_regency_id');
            $table->index('pendidikan', 'idx_pencaker_pendidikan');
        });
    }

    public function down(): void
    {
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->dropIndex('idx_pencaker_province_id');
            $table->dropIndex('idx_pencaker_regency_id');
            $table->dropIndex('idx_pencaker_pendidikan');
        });
    }
};
