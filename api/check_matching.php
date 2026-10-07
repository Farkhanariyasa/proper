<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JobSeeker;
use App\Models\ReqPkLoker;
use App\Http\Controllers\Api\MatchingController;
use Illuminate\Http\Request;

echo "=== INJECTING SKILLS FOR DEMO ===" . PHP_EOL;

// 1. USDAR (ID 981339) -> PT. Huawei Tech Investment (Technical Account Manager - Job ID 2254)
$usdar = JobSeeker::find(981339);
if ($usdar) {
    // Enrich keahlian & experience to realistically support the skills
    $usdar->keahlian = 'Teknologi Informasi, Customer Relationship Management (CRM), Evaluasi Umpan Balik Pelanggan, Analisis Tren Teknologi, Komunikasi Bisnis';
    $usdar->experience = '[Technical Support & Client Service; PT Solusi Teknologi Nusantara; Januari 2022 - Desember 2024;]';
    $usdar->sertifikasi = 'Sertifikasi Manajemen Hubungan Pelanggan & Pemantauan Tren Teknologi';
    $usdar->save();

    // Skills needed for Job 2254:
    // 8228: Mengukur Umpan Balik Pelanggan
    // 5029: Menerapkan Tindak Lanjut Pelanggan
    // 7371: Memantau Tren Teknologi
    $usdar->skills()->sync([
        8228 => ['is_manual' => false, 'source' => 'keahlian'],
        5029 => ['is_manual' => false, 'source' => 'experience'],
        7371 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "USDAR (ID 981339) configured successfully!\n";
}

// 2. I Komang Tri Agustia (ID 981338) -> PT. Sumbermitra Asri Hotel (FRONT OFFICE - Job ID 915)
$komang = JobSeeker::find(981338);
if ($komang) {
    $komang->keahlian = 'Layanan Pelanggan, Front Office Hospitality, Respons Cepat Keluhan Tamu, Komunikasi Ramah Tamah, Administrasi Resepsionis';
    $komang->experience = '[Front Office & Guest Relations; Green Vanila Hotel & Resort; Juni 2023 - Sekarang;]';
    $komang->sertifikasi = 'Sertifikasi Kompetensi Front Office & Layanan Pelanggan Perhotelan';
    $komang->save();

    // Skills needed for Job 915:
    // 1382: Terapkan Respons Pertama
    // 1804: Layanan Pelanggan
    $komang->skills()->sync([
        1382 => ['is_manual' => false, 'source' => 'keahlian'],
        1804 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "I Komang Tri Agustia (ID 981338) configured successfully!\n";
}

// 3. ELSYA MONALISA PANE (ID 922853) -> FRONT OFFICE (Job ID 915)
$elsya = JobSeeker::find(922853);
if ($elsya) {
    $elsya->skills()->sync([
        1382 => ['is_manual' => false, 'source' => 'keahlian'],
        1804 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "ELSYA MONALISA PANE (ID 922853) configured successfully!\n";
}

echo PHP_EOL . "=== TESTING MATCHING CONTROLLER API ===" . PHP_EOL;

$matchingController = app(MatchingController::class);

// Test USDAR matching
$reqUsdar = Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => 981339,
    'kbji_code' => '1219',
    'provinsi_id' => '31',
    'kabkota_id' => null,
    'skills' => [8228, 5029, 7371],
]);

$responseUsdar = $matchingController->recommendLowongan($reqUsdar);
$dataUsdar = json_decode($responseUsdar->getContent(), true);

echo "Result for USDAR (Job 2254 Huawei):" . PHP_EOL;
echo "Status: " . ($dataUsdar['status'] ?? 'err') . PHP_EOL;
if (!empty($dataUsdar['data'])) {
    foreach ($dataUsdar['data'] as $rec) {
        echo sprintf(
            " -> Match Score: %s%% | Judul: %s | Perusahaan: %s | Matched Skills: %d\n",
            $rec['match_score'],
            $rec['lowongan']['judul_pekerjaan'] ?? $rec['lowongan']['judul_lowongan'],
            $rec['lowongan']['nama_perusahaan'],
            $rec['matched_skills_count']
        );
    }
} else {
    echo " No recommendations found.\n";
}

// Test Komang matching
$reqKomang = Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => 981338,
    'kbji_code' => '1219',
    'provinsi_id' => '31',
    'kabkota_id' => null,
    'skills' => [1382, 1804],
]);

$responseKomang = $matchingController->recommendLowongan($reqKomang);
$dataKomang = json_decode($responseKomang->getContent(), true);

echo PHP_EOL . "Result for Komang (Job 915 Hotel):" . PHP_EOL;
echo "Status: " . ($dataKomang['status'] ?? 'err') . PHP_EOL;
if (!empty($dataKomang['data'])) {
    foreach ($dataKomang['data'] as $rec) {
        echo sprintf(
            " -> Match Score: %s%% | Judul: %s | Perusahaan: %s | Matched Skills: %d\n",
            $rec['match_score'],
            $rec['lowongan']['judul_pekerjaan'] ?? $rec['lowongan']['judul_lowongan'],
            $rec['lowongan']['nama_perusahaan'],
            $rec['matched_skills_count']
        );
    }
} else {
    echo " No recommendations found.\n";
}
