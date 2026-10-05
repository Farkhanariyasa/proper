<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KbjiSeeder extends Seeder
{
    /**
     * Seed master Klasifikasi Baku Jabatan Indonesia (KBJI 2020) ke tabel kbji_classifications_2026.
     * Jalankan: php artisan db:seed --class=KbjiSeeder
     */
    public function run(): void
    {
        DB::transaction(function () {
            DB::table('kbji_classifications_2026')->delete();

            // File SQL ditulis untuk struktur tabel lama (dengan isco_code):
            // muat ke tabel sementara, lalu salin ke kbji_classifications_2026
            DB::unprepared('
                CREATE TEMP TABLE kbji_seed (
                    code varchar(20), title varchar(200), level varchar(20),
                    parent_code varchar(20), description text, isco_code varchar(20)
                ) ON COMMIT DROP
            ');
            $sql = str_replace(
                'INSERT INTO kbji_classifications (',
                'INSERT INTO kbji_seed (',
                file_get_contents(__DIR__ . '/data/kbji_2020.sql')
            );
            DB::unprepared($sql);
            DB::unprepared('
                INSERT INTO kbji_classifications_2026 (code, title, level, parent_code, description)
                SELECT code, title, level::' . $this->levelColumnType() . ', parent_code, description FROM kbji_seed
            ');
        });
    }

    /**
     * Di Zeabur kolom level bertipe enum PostgreSQL (kbji_level), sedangkan
     * tabel yang dibuat migration memakai varchar — cast mengikuti tipe aslinya.
     */
    private function levelColumnType(): string
    {
        $column = DB::selectOne("
            SELECT data_type, udt_name FROM information_schema.columns
            WHERE table_name = 'kbji_classifications_2026' AND column_name = 'level'
        ");

        return $column && $column->data_type === 'USER-DEFINED' ? $column->udt_name : 'varchar';
    }
}
