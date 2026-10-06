<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

echo "Memulai proses matching (Metode Fast Map)...\n";

// Load Master Data ke Memory (sangat kecil, sangat cepat)
$provinces = DB::table('provinces')->get();
$regencies = DB::table('regencies')->get();
$educations = DB::table('education_levels')->get();

// ============================================
// 1. PROVINSI
// ============================================
echo "\n1. Mapping Provinsi...\n";
$distinctProv = DB::table('req_pk_pencaker')
    ->whereNotNull('provinsi')
    ->whereNull('province_id')
    ->distinct()
    ->pluck('provinsi');

$provCount = 0;
foreach ($distinctProv as $provStr) {
    $matched = $provinces->first(fn($p) => strtoupper($p->name) === strtoupper(trim($provStr)));
    if ($matched) {
        $affected = DB::table('req_pk_pencaker')
            ->where('provinsi', $provStr)
            ->whereNull('province_id')
            ->update(['province_id' => $matched->id]);
        $provCount += $affected;
    }
}
echo "-> $provCount baris Provinsi berhasil di-update.\n";

// ============================================
// 2. KABUPATEN
// ============================================
echo "\n2. Mapping Kabupaten/Kota...\n";
$distinctKab = DB::table('req_pk_pencaker')
    ->whereNotNull('kab_kota')
    ->whereNull('regency_id')
    ->distinct()
    ->pluck('kab_kota');

$kabCount = 0;
foreach ($distinctKab as $kabStr) {
    // Normalisasi string dari SIAPkerja
    $normalized = strtoupper(trim($kabStr));
    $normalized = str_replace('KAB. ', 'KABUPATEN ', $normalized);
    $normalized = str_replace('KOTA ADM. ', 'KOTA ', $normalized);
    
    $matched = $regencies->first(fn($r) => strtoupper($r->name) === $normalized);
    if ($matched) {
        $affected = DB::table('req_pk_pencaker')
            ->where('kab_kota', $kabStr)
            ->whereNull('regency_id')
            ->update(['regency_id' => $matched->id]);
        $kabCount += $affected;
    }
}
echo "-> $kabCount baris Kabupaten/Kota berhasil di-update.\n";

// ============================================
// 3. PENDIDIKAN
// ============================================
echo "\n3. Mapping Pendidikan...\n";
$distinctEdu = DB::table('req_pk_pencaker')
    ->whereNotNull('pendidikan')
    ->whereNull('education_level_id')
    ->distinct()
    ->pluck('pendidikan');

$eduCount = 0;
foreach ($distinctEdu as $eduStr) {
    $p = strtoupper(trim($eduStr));
    $mappedName = null;
    
    if (in_array($p, ['S1', 'D4', 'SARJANA'])) $mappedName = 'Sarjana / Diploma IV (S1 / D4)';
    elseif (in_array($p, ['D1', 'D2', 'D3'])) $mappedName = 'Diploma I - III (D1 - D3)';
    elseif (in_array($p, ['SMA', 'SMK', 'SMA ATAU SEDERAJAT', 'STM', 'SLTA SEDERAJAT'])) $mappedName = 'SMA / SMK / Sederajat';
    elseif (in_array($p, ['S2', 'MAGISTER'])) $mappedName = 'Magister (S2)';
    elseif (in_array($p, ['S3', 'DOKTOR'])) $mappedName = 'Doktoral (S3)';
    elseif (in_array($p, ['SMP ATAU SEDERAJAT', 'SMP', 'SLTP SEDERAJAT'])) $mappedName = 'SMP / Sederajat';
    elseif (in_array($p, ['SD ATAU SEDERAJAT', 'SD'])) $mappedName = 'SD / Sederajat';

    if ($mappedName) {
        $matched = $educations->firstWhere('name', $mappedName);
        if ($matched) {
            $affected = DB::table('req_pk_pencaker')
                ->where('pendidikan', $eduStr)
                ->whereNull('education_level_id')
                ->update(['education_level_id' => $matched->id]);
            $eduCount += $affected;
        }
    }
}
echo "-> $eduCount baris Pendidikan berhasil di-update.\n";

echo "\nProses Matching Selesai!\n";
