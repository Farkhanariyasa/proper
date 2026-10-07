<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JobSeeker;
use App\Models\ReqPkLoker;
use App\Http\Controllers\Api\MatchingController;
use Illuminate\Http\Request;

echo "=== MENYETEL 3 JENIS KECOCOKAN DALAM 1 KALI PROSES REKOMENDASI ===" . PHP_EOL;

// -------------------------------------------------------------------------
// KANDIDAT 1: FATMA NUR AZMI BUSTAMI (ID: 920730)
// Target Keyword: "Teacher"
// Menghasilkan:
// - Chemistry & Physics Teacher -> 100% (3/3 skill) -> SANGAT COCOK
// - Math Teacher -> 50% (2/4 skill) -> CUKUP SESUAI / PERLU PELATIHAN (40-69%)
// - Economics / Geography / English Teacher -> 0% - 25% -> PERLU PELATIHAN (<40%)
// -------------------------------------------------------------------------
$fatma = JobSeeker::find(920730);
if ($fatma) {
    $fatma->keahlian = 'Pendidikan Sains, Pembelajaran IPA dan Kimia, Siswa Tutor, Memecahkan Masalah, Keselamatan Kerja Laboratorium';
    $fatma->experience = '[Guru Tutor Sains; Bimbel Prestasi Cerdas; 2021 - 2024; Membimbing siswa tutor materi sains, membantu memecahkan masalah belajar, serta mengawasi kesehatan dan keselamatan di tempat kerja laboratorium.]';
    $fatma->sertifikasi = 'Sertifikasi Pendidik Sains & K3 Laboratorium';
    $fatma->save();

    // 10139: Memecahkan Masalah
    // 6128: Siswa Tutor
    // 2010: Kesehatan dan Keselamatan di Tempat Kerja
    $fatma->skills()->sync([
        10139 => ['is_manual' => false, 'source' => 'keahlian'],
        6128  => ['is_manual' => false, 'source' => 'experience'],
        2010  => ['is_manual' => false, 'source' => 'sertifikasi'],
    ]);
    echo "[OK] FATMA NUR AZMI BUSTAMI (ID 920730) disetel untuk formasi 'Teacher'\n";
}

// -------------------------------------------------------------------------
// KANDIDAT 2: USDAR (ID: 981339)
// Target Keyword: "Manager"
// Menghasilkan:
// - Technical Account Manager -> 100% (3/3 skill) -> SANGAT COCOK
// - Marketing Manager -> 47% (7/15 skill) -> CUKUP SESUAI / PERLU PELATIHAN (40-69%)
// - Building Manager -> 0% (0/15 skill) -> PERLU PELATIHAN (<40%)
// -------------------------------------------------------------------------
$usdar = JobSeeker::find(981339);
if ($usdar) {
    $usdar->keahlian = 'Teknologi Informasi, Customer Relationship Management (CRM), Evaluasi Umpan Balik Pelanggan, Analisis Tren Teknologi, Komunikasi, Menjual Produk, Memecahkan Masalah, Perencanaan Strategis';
    $usdar->experience = '[Technical Account Manager; PT Solusi Teknologi Nusantara; 2021 - 2024; Mengukur umpan balik pelanggan enterprise, menerapkan tindak lanjut pelanggan, memantau tren teknologi, membangun hubungan bisnis, dan mencapai target penjualan.]';
    $usdar->sertifikasi = 'Sertifikasi Manajemen Hubungan Pelanggan & Strategic Sales';
    $usdar->save();

    // Skills needed for Technical Account Manager (100% = 3/3):
    // 8228, 5029, 7371
    // Plus 7 skills from Marketing Manager (7/15 = 47%):
    // 11845, 12646, 11113, 1814, 2931, 10139, 14131
    $usdar->skills()->sync([
        8228  => ['is_manual' => false, 'source' => 'keahlian'],
        5029  => ['is_manual' => false, 'source' => 'experience'],
        7371  => ['is_manual' => false, 'source' => 'keahlian'],
        11845 => ['is_manual' => false, 'source' => 'experience'],
        12646 => ['is_manual' => false, 'source' => 'experience'],
        11113 => ['is_manual' => false, 'source' => 'experience'],
        1814  => ['is_manual' => false, 'source' => 'keahlian'],
        2931  => ['is_manual' => false, 'source' => 'keahlian'],
        10139 => ['is_manual' => false, 'source' => 'keahlian'],
        14131 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "[OK] USDAR (ID 981339) disetel untuk formasi 'Manager'\n";
}

echo PHP_EOL . "=== PENGUJIAN API REKOMENDASI UNTUK HASIL 3 TIER ===" . PHP_EOL;

$matchingController = app(MatchingController::class);

// Test 1: FATMA dengan keyword 'Teacher'
echo PHP_EOL . "--- HASIL REKOMENDASI UNTUK FATMA (Keyword: 'Teacher') ---" . PHP_EOL;
$reqFatma = Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => 920730,
    'pekerjaan' => 'Teacher',
    'skills' => [10139, 6128, 2010],
]);
$resFatma = json_decode($matchingController->recommendLowongan($reqFatma)->getContent(), true);

if (!empty($resFatma['data'])) {
    foreach ($resFatma['data'] as $idx => $r) {
        $score = $r['match_score'];
        $status = $score >= 70 ? 'SANGAT COCOK (Tier 1)' : ($score >= 40 ? 'CUKUP SESUAI / SKILL GAP (Tier 2)' : 'PERLU PELATIHAN (Tier 3)');
        echo sprintf(
            "#%d | Skor: %3d%% | Status: %-35s | Judul: %s | Cocok: %d/%d skill\n",
            $idx + 1,
            $score,
            $status,
            $r['lowongan']['judul_pekerjaan'],
            $r['matched_skills_count'],
            count($r['lowongan']['skills'])
        );
    }
}

// Test 2: USDAR dengan keyword 'Manager'
echo PHP_EOL . "--- HASIL REKOMENDASI UNTUK USDAR (Keyword: 'Manager') ---" . PHP_EOL;
$reqUsdar = Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => 981339,
    'pekerjaan' => 'Manager',
    'skills' => [8228, 5029, 7371, 11845, 12646, 11113, 1814, 2931, 10139, 14131],
]);
$resUsdar = json_decode($matchingController->recommendLowongan($reqUsdar)->getContent(), true);

if (!empty($resUsdar['data'])) {
    foreach ($resUsdar['data'] as $idx => $r) {
        $score = $r['match_score'];
        $status = $score >= 70 ? 'SANGAT COCOK (Tier 1)' : ($score >= 40 ? 'CUKUP SESUAI / SKILL GAP (Tier 2)' : 'PERLU PELATIHAN (Tier 3)');
        echo sprintf(
            "#%d | Skor: %3d%% | Status: %-35s | Judul: %s | Cocok: %d/%d skill\n",
            $idx + 1,
            $score,
            $status,
            $r['lowongan']['judul_pekerjaan'],
            $r['matched_skills_count'],
            count($r['lowongan']['skills'])
        );
    }
}
