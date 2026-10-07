<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JobSeeker;
use App\Models\ReqPkLoker;
use App\Http\Controllers\Api\MatchingController;
use Illuminate\Http\Request;

echo "=== MENGONDISIKAN DATA KANDIDAT DEMO ===" . PHP_EOL;

// -------------------------------------------------------------
// SEKTOR 1: IT / ACCOUNT MANAGEMENT (Lowongan ID 2254: Technical Account Manager - PT. Huawei Tech Investment)
// Skill Lowongan:
// - 8228: Mengukur Umpan Balik Pelanggan
// - 5029: Menerapkan Tindak Lanjut Pelanggan
// - 7371: Memantau Tren Teknologi
// -------------------------------------------------------------

// 1A. USDAR (ID 981339) -> MATCH 100% (3/3 Skill)
$usdar = JobSeeker::find(981339);
if ($usdar) {
    $usdar->keahlian = 'Teknologi Informasi, Customer Relationship Management (CRM), Evaluasi Umpan Balik Pelanggan, Analisis Tren Teknologi, Komunikasi Bisnis';
    $usdar->experience = '[Technical Account & Client Service; PT Solusi Teknologi Nusantara; Januari 2021 - Desember 2024; Mengukur kepuasan dan umpan balik pelanggan enterprise, menerapkan tindak lanjut pelanggan, serta memantau perkembangan tren teknologi cloud dan telekomunikasi.]';
    $usdar->sertifikasi = 'Sertifikasi Manajemen Hubungan Pelanggan & Pemantauan Tren Teknologi';
    $usdar->save();

    $usdar->skills()->sync([
        8228 => ['is_manual' => false, 'source' => 'keahlian'],
        5029 => ['is_manual' => false, 'source' => 'experience'],
        7371 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "[OK] USDAR (ID 981339) disetel 100% untuk Technical Account Manager (3/3 skill)\n";
}

// 1B. YUDA HANDI SUMATEJA (ID 920682) -> MATCH 67% (2/3 Skill - Masuk Rentang 40-69%)
// Memiliki skill 8228 dan 5029, tapi BELUM memiliki 7371 (Memantau Tren Teknologi)
$yuda = JobSeeker::find(920682);
if ($yuda) {
    $yuda->keahlian = 'Layanan Pelanggan, Evaluasi Umpan Balik Pelanggan, Komunikasi, Administrasi';
    $yuda->experience = '[Customer Relations Staff; PT Mitra Niaga Sejahtera; 2022 - 2024; Bertanggung jawab melayani keluhan pelanggan, mengukur umpan balik pelanggan, serta menerapkan tindak lanjut pelanggan secara berkala.]';
    $yuda->sertifikasi = 'Pelatihan Pelayanan Prima & Retensi Pelanggan';
    $yuda->save();

    $yuda->skills()->sync([
        8228 => ['is_manual' => false, 'source' => 'keahlian'],
        5029 => ['is_manual' => false, 'source' => 'experience'],
    ]);
    echo "[OK] Yuda Handi Sumateja (ID 920682) disetel 67% untuk Technical Account Manager (2/3 skill - GAP: 7371 Memantau Tren Teknologi)\n";
}

// -------------------------------------------------------------
// SEKTOR 2: HOSPITALITY / FRONT OFFICE (Lowongan ID 915: FRONT OFFICE - PT. Sumbermitra Asri Hotel)
// Skill Lowongan:
// - 1382: Terapkan Respons Pertama
// - 1804: Layanan Pelanggan
// -------------------------------------------------------------

// 2A. I KOMANG TRI AGUSTIA (ID 981338) -> MATCH 100% (2/2 Skill)
$komang = JobSeeker::find(981338);
if ($komang) {
    $komang->keahlian = 'Layanan Pelanggan, Front Office Hospitality, Respons Cepat Keluhan Tamu, Komunikasi Ramah Tamah, Administrasi Resepsionis';
    $komang->experience = '[Front Desk Agent; Green Vanila Hotel & Resort; Juni 2023 - Sekarang; Menerapkan respons pertama penanganan keluhan tamu, memberikan layanan pelanggan prima perhotelan.]';
    $komang->sertifikasi = 'Sertifikasi Kompetensi Front Office & Layanan Pelanggan Perhotelan BNSP';
    $komang->save();

    $komang->skills()->sync([
        1382 => ['is_manual' => false, 'source' => 'keahlian'],
        1804 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "[OK] I Komang Tri Agustia (ID 981338) disetel 100% untuk FRONT OFFICE (2/2 skill)\n";
}

// 2B. ELSYA MONALISA PANE (ID 922853) -> MATCH 50% (1/2 Skill - Masuk Rentang 40-69%)
// Memiliki skill 1804 (Layanan Pelanggan), tapi BELUM memiliki 1382 (Terapkan Respons Pertama)
$elsya = JobSeeker::find(922853);
if ($elsya) {
    $elsya->keahlian = 'Layanan Pelanggan, Komunikasi, Administrasi';
    $elsya->experience = '[Staff Layanan; CV Surya Abadi; 2023 - 2024; Melayani kebutuhan pelanggan dan administrasi kasir.]';
    $elsya->sertifikasi = 'Sertifikat Customer Service Excellence';
    $elsya->save();

    $elsya->skills()->sync([
        1804 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "[OK] ELSYA MONALISA PANE (ID 922853) disetel 50% untuk FRONT OFFICE (1/2 skill - GAP: 1382 Terapkan Respons Pertama)\n";
}

echo PHP_EOL . "=== VALIDASI HASIL MATCHING CONTROLLER ===" . PHP_EOL;

$matchingController = app(MatchingController::class);

// 1. Validasi USDAR (Target 100%)
$req1 = Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => 981339,
    'pekerjaan' => 'Account Manager',
    'skills' => [8228, 5029, 7371],
]);
$res1 = json_decode($matchingController->recommendLowongan($req1)->getContent(), true);
$top1 = $res1['data'][0] ?? null;
echo sprintf("1. USDAR -> Lowongan: %s | Skor: %d%% | Cocok: %d skill\n",
    $top1['lowongan']['judul_pekerjaan'] ?? '-',
    $top1['match_score'] ?? 0,
    $top1['matched_skills_count'] ?? 0
);

// 2. Validasi YUDA (Target 67%)
$req2 = Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => 920682,
    'pekerjaan' => 'Account Manager',
    'skills' => [8228, 5029],
]);
$res2 = json_decode($matchingController->recommendLowongan($req2)->getContent(), true);
$top2 = $res2['data'][0] ?? null;
echo sprintf("2. Yuda Handi Sumateja -> Lowongan: %s | Skor: %d%% | Cocok: %d skill\n",
    $top2['lowongan']['judul_pekerjaan'] ?? '-',
    $top2['match_score'] ?? 0,
    $top2['matched_skills_count'] ?? 0
);

// 3. Validasi KOMANG (Target 100%)
$req3 = Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => 981338,
    'pekerjaan' => 'Front Office',
    'skills' => [1382, 1804],
]);
$res3 = json_decode($matchingController->recommendLowongan($req3)->getContent(), true);
$top3 = $res3['data'][0] ?? null;
echo sprintf("3. I Komang Tri Agustia -> Lowongan: %s | Skor: %d%% | Cocok: %d skill\n",
    $top3['lowongan']['judul_pekerjaan'] ?? '-',
    $top3['match_score'] ?? 0,
    $top3['matched_skills_count'] ?? 0
);

// 4. Validasi ELSYA (Target 50%)
$req4 = Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => 922853,
    'pekerjaan' => 'Front Office',
    'skills' => [1804],
]);
$res4 = json_decode($matchingController->recommendLowongan($req4)->getContent(), true);
$top4 = $res4['data'][0] ?? null;
echo sprintf("4. ELSYA MONALISA PANE -> Lowongan: %s | Skor: %d%% | Cocok: %d skill\n",
    $top4['lowongan']['judul_pekerjaan'] ?? '-',
    $top4['match_score'] ?? 0,
    $top4['matched_skills_count'] ?? 0
);
