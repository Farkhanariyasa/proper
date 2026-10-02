<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KbjiSeeder extends Seeder
{
    /**
     * Seed master Klasifikasi Baku Jabatan Indonesia (KBJI 2020).
     * Jalankan: php artisan db:seed --class=KbjiSeeder
     */
    public function run(): void
    {
        DB::transaction(function () {
            DB::table('kbji_classifications')->delete();
            DB::unprepared(file_get_contents(__DIR__ . '/data/kbji_2020.sql'));
        });
    }
}
