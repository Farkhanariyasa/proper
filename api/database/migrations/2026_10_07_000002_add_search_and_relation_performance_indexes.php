<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Index untuk pencarian nama pada 920k pencaker
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->index('name', 'idx_pencaker_name');
        });

        // 2. Index untuk relasi pivot lowongan_skills -> vac_id
        if (Schema::hasTable('lowongan_skills')) {
            Schema::table('lowongan_skills', function (Blueprint $table) {
                $table->index('vac_id', 'idx_lowongan_skills_vac_id');
            });
        }

        // 3. Index untuk pencarian lowongan
        Schema::table('req_pk_loker', function (Blueprint $table) {
            $table->index('judul_pekerjaan', 'idx_loker_judul');
            $table->index('nama_perusahaan', 'idx_loker_perusahaan');
            $table->index(['status_loker', 'tanggal_tayang'], 'idx_loker_status_tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('req_pk_pencaker', function (Blueprint $table) {
            $table->dropIndex('idx_pencaker_name');
        });

        if (Schema::hasTable('lowongan_skills')) {
            Schema::table('lowongan_skills', function (Blueprint $table) {
                $table->dropIndex('idx_lowongan_skills_vac_id');
            });
        }

        Schema::table('req_pk_loker', function (Blueprint $table) {
            $table->dropIndex('idx_loker_judul');
            $table->dropIndex('idx_loker_perusahaan');
            $table->dropIndex('idx_loker_status_tanggal');
        });
    }
};
