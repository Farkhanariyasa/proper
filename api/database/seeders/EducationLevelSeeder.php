<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            ['id' => 1, 'name' => 'SD / Sederajat', 'sort_order' => 1],
            ['id' => 2, 'name' => 'SMP / Sederajat', 'sort_order' => 2],
            ['id' => 3, 'name' => 'SMA / SMK / Sederajat', 'sort_order' => 3],
            ['id' => 4, 'name' => 'Diploma I - III (D1 - D3)', 'sort_order' => 4],
            ['id' => 5, 'name' => 'Sarjana / Diploma IV (S1 / D4)', 'sort_order' => 5],
            ['id' => 6, 'name' => 'Magister (S2)', 'sort_order' => 6],
            ['id' => 7, 'name' => 'Doktoral (S3)', 'sort_order' => 7],
        ];

        foreach ($levels as $level) {
            DB::table('education_levels')->updateOrInsert(
                ['id' => $level['id']],
                [
                    'name' => $level['name'],
                    'sort_order' => $level['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
