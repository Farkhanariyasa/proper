<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

echo "Memulai proses matching ke master data...\n";

$maxId = DB::table('req_pk_pencaker')->max('id') ?? 0;
$chunkSize = 2500; // Chunk diperkecil agar progress bar berkedip lebih cepat (real-time)
$totalProcessed = 0;

echo "Total rentang ID: $maxId. Memproses per $chunkSize baris...\n\n";

for ($i = 0; $i <= $maxId; $i += $chunkSize) {
    $end = $i + $chunkSize;
    $current = min($totalProcessed, $maxId);
    
    // Tampilkan log progress di baris yang sama (\r)
    echo "\rProgress: $current / $maxId baris diproses...";
    
    // 1. Provinsi
    DB::update("
        UPDATE req_pk_pencaker p 
        SET province_id = master.id 
        FROM provinces master 
        WHERE p.id >= ? AND p.id < ?
          AND UPPER(TRIM(p.provinsi)) = UPPER(master.name)
          AND p.province_id IS NULL
    ", [$i, $end]);

    // 2. Kabupaten
    DB::update("
        UPDATE req_pk_pencaker p 
        SET regency_id = master.id 
        FROM regencies master 
        WHERE p.id >= ? AND p.id < ?
          AND REPLACE(REPLACE(UPPER(TRIM(p.kab_kota)), 'KAB. ', 'KABUPATEN '), 'KOTA ADM. ', 'KOTA ') = UPPER(master.name)
          AND p.regency_id IS NULL
    ", [$i, $end]);

    // 3. Pendidikan
    DB::update("
        UPDATE req_pk_pencaker p
        SET education_level_id = e.id
        FROM education_levels e
        WHERE p.id >= ? AND p.id < ?
          AND p.education_level_id IS NULL AND (
          (UPPER(TRIM(p.pendidikan)) IN ('S1', 'D4', 'SARJANA') AND e.name = 'Sarjana / Diploma IV (S1 / D4)') OR
          (UPPER(TRIM(p.pendidikan)) IN ('D1', 'D2', 'D3') AND e.name = 'Diploma I - III (D1 - D3)') OR
          (UPPER(TRIM(p.pendidikan)) IN ('SMA', 'SMK', 'SMA ATAU SEDERAJAT', 'STM', 'SLTA SEDERAJAT') AND e.name = 'SMA / SMK / Sederajat') OR
          (UPPER(TRIM(p.pendidikan)) IN ('S2', 'MAGISTER') AND e.name = 'Magister (S2)') OR
          (UPPER(TRIM(p.pendidikan)) IN ('S3', 'DOKTOR') AND e.name = 'Doktoral (S3)') OR
          (UPPER(TRIM(p.pendidikan)) IN ('SMP ATAU SEDERAJAT', 'SMP', 'SLTP SEDERAJAT') AND e.name = 'SMP / Sederajat') OR
          (UPPER(TRIM(p.pendidikan)) IN ('SD ATAU SEDERAJAT', 'SD') AND e.name = 'SD / Sederajat')
        )
    ", [$i, $end]);

    $totalProcessed += $chunkSize;
}

// PENGECEKAN SISA
echo "\n=============================================\n";
echo "Mengecek data yang masih GAGAL match (NULL)...\n";
$unmatchedProv = DB::table('req_pk_pencaker')->whereNotNull('provinsi')->whereNull('province_id')->count();
$unmatchedKab = DB::table('req_pk_pencaker')->whereNotNull('kab_kota')->whereNull('regency_id')->count();
$unmatchedEdu = DB::table('req_pk_pencaker')->whereNotNull('pendidikan')->whereNull('education_level_id')->count();

echo "Sisa yang belum match:\n";
echo "- Provinsi : $unmatchedProv baris\n";
echo "- Kab/Kota : $unmatchedKab baris\n";
echo "- Pendidikan: $unmatchedEdu baris\n";

echo "\nProses Selesai!\n";
