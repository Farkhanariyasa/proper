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
        Schema::create('lowongan_kerja', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 200)->unique();
            $table->string('nama_perusahaan', 150);
            $table->string('judul_lowongan', 150);

            // Relasi KBJI (Jabatan Standar)
            $table->foreignId('kbji_id')->constrained('kbji_classifications');
            $table->bigInteger('lapangan_usaha_id')->nullable()->index(); // KBLI

            // Deskripsi & Sistem Kerja
            $table->text('deskripsi_pekerjaan');
            $table->string('tipe_pekerjaan', 30)->default('Full-Time');
            $table->string('sistem_kerja', 20)->default('WFO');
            $table->unsignedInteger('jumlah_kebutuhan')->default(1);

            // Kualifikasi & Persyaratan
            $table->foreignId('education_level_id')->constrained('education_levels');
            $table->string('jurusan_studi', 150)->nullable();
            $table->unsignedSmallInteger('pengalaman_minimal_tahun')->default(0);
            $table->unsignedSmallInteger('usia_minimal')->nullable();
            $table->unsignedSmallInteger('usia_maksimal')->nullable();
            $table->string('jenis_kelamin', 20)->default('Semua');
            $table->boolean('is_disabilitas')->default(false);
            $table->text('persyaratan_tambahan')->nullable();

            // Lokasi & Penempatan
            $table->char('provinsi_id', 2);
            $table->char('regency_id', 5);
            $table->text('alamat_lengkap_penempatan')->nullable();

            // Kompensasi & Gaji
            $table->boolean('gaji_tampilkan')->default(true);
            $table->decimal('gaji_minimal', 14, 2)->nullable();
            $table->decimal('gaji_maksimal', 14, 2)->nullable();

            // Meta Data & Kontrol Publikasi (tanpa views_count)
            $table->string('status_lowongan', 20)->default('Draft');
            $table->timestamp('tanggal_buka')->nullable();
            $table->timestamp('tanggal_tutup');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys Wilayah
            $table->foreign('provinsi_id')->references('id')->on('provinces')->cascadeOnDelete();
            $table->foreign('regency_id')->references('id')->on('regencies')->cascadeOnDelete();

            // Indexes
            $table->index(['status_lowongan', 'tanggal_tutup']);
            $table->index(['provinsi_id', 'regency_id']);
            $table->index('kbji_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lowongan_kerja');
    }
};
