<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class WilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provincesFile = database_path('data/provinces.json');
        $regenciesFile = database_path('data/regencies.json');

        if (File::exists($provincesFile)) {
            $provinces = json_decode(File::get($provincesFile), true);
            foreach (array_chunk($provinces, 100) as $chunk) {
                DB::table('provinces')->upsert(
                    $chunk,
                    ['id'],
                    ['name']
                );
            }
        }

        if (File::exists($regenciesFile)) {
            $regencies = json_decode(File::get($regenciesFile), true);
            foreach (array_chunk($regencies, 100) as $chunk) {
                DB::table('regencies')->upsert(
                    $chunk,
                    ['id'],
                    ['province_id', 'name']
                );
            }
        }
    }
}
