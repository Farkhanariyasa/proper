<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->index(['provinsi', 'kab_kota'], 'idx_pencaker_prov_kab');
            $table->index('status_bekerja', 'idx_pencaker_status_bekerja');
        });

        Schema::table('req_pk_loker', function (Blueprint $table) {
            $table->index('tanggal_tayang', 'idx_loker_tanggal_tayang');
        });
    }

    public function down(): void
    {
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->dropIndex('idx_pencaker_prov_kab');
            $table->dropIndex('idx_pencaker_status_bekerja');
        });

        Schema::table('req_pk_loker', function (Blueprint $table) {
            $table->dropIndex('idx_loker_tanggal_tayang');
        });
    }
};
