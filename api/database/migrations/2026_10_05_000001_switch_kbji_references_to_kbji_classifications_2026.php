<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memindahkan master KBJI ke tabel kbji_classifications_2026.
 *
 * Isi tabel baru sama dengan kbji_classifications (kode, judul, deskripsi),
 * tetapi ID-nya berbeda. Karena itu kbji_id pada lowongan_kerja & job_seekers
 * dikonversi berdasarkan kode KBJI sebelum foreign key dipindahkan.
 */
return new class extends Migration
{
    private const OLD_TABLE = 'kbji_classifications';
    private const NEW_TABLE = 'kbji_classifications_2026';

    public function up(): void
    {
        // Lingkungan baru (lokal/CI) belum punya tabel 2026 → buat dengan struktur yang sama
        if (!Schema::hasTable(self::NEW_TABLE)) {
            Schema::create(self::NEW_TABLE, function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('title', 200);
                $table->enum('level', [
                    'major_group',
                    'sub_major_group',
                    'minor_group',
                    'unit_group',
                    'occupation',
                ])->index();
                $table->string('parent_code', 20)->nullable()->index();
                $table->text('description')->nullable();
            });

            DB::statement('
                INSERT INTO ' . self::NEW_TABLE . ' (code, title, level, parent_code, description)
                SELECT code, title, level, parent_code, description FROM ' . self::OLD_TABLE . '
            ');
        }

        $this->repointReferences(self::OLD_TABLE, self::NEW_TABLE);
    }

    public function down(): void
    {
        $this->repointReferences(self::NEW_TABLE, self::OLD_TABLE);
    }

    private function repointReferences(string $from, string $to): void
    {
        Schema::table('lowongan_kerja', fn (Blueprint $table) => $table->dropForeign(['kbji_id']));
        Schema::table('job_seekers', fn (Blueprint $table) => $table->dropForeign(['kbji_id']));

        foreach (['lowongan_kerja', 'job_seekers'] as $referencing) {
            DB::statement("
                UPDATE {$referencing} AS r
                SET kbji_id = t.id
                FROM {$from} AS f
                JOIN {$to} AS t ON t.code = f.code
                WHERE r.kbji_id = f.id
            ");
        }

        Schema::table('lowongan_kerja', function (Blueprint $table) use ($to) {
            $table->foreign('kbji_id')->references('id')->on($to);
        });
        Schema::table('job_seekers', function (Blueprint $table) use ($to) {
            $table->foreign('kbji_id')->references('id')->on($to)->nullOnDelete();
        });
    }
};
