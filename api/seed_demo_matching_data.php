<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JobSeeker;
use App\Models\ReqPkLoker;
use Illuminate\Support\Facades\DB;

echo "=== MEMASUKKAN / MEMPERBARUI DATA DUMMY DEMO KE DATABASE ===" . PHP_EOL . PHP_EOL;

// 1. Kondisikan Lowongan 1: Technical Account Manager (PT. Huawei Tech Investment, ID: 2254)
$loker1 = ReqPkLoker::find(2254);
if ($loker1) {
    $loker1->status_loker = 'published';
    $loker1->education_level_id = 5; // S1 / D4
    $loker1->save();

    // Pastikan skills terhubung di lowongan_skills
    // Skill: 8228 (Mengukur Umpan Balik Pelanggan), 5029 (Menerapkan Tindak Lanjut Pelanggan), 7371 (Memantau Tren Teknologi)
    DB::table('lowongan_skills')->where('vac_id', $loker1->vac_id)->delete();
    DB::table('lowongan_skills')->insert([
        ['vac_id' => $loker1->vac_id, 'esco_skill_id' => 8228, 'tipe_keahlian' => 'wajib', 'skor' => '1.0', 'metode' => 'curated'],
        ['vac_id' => $loker1->vac_id, 'esco_skill_id' => 5029, 'tipe_keahlian' => 'wajib', 'skor' => '1.0', 'metode' => 'curated'],
        ['vac_id' => $loker1->vac_id, 'esco_skill_id' => 7371, 'tipe_keahlian' => 'wajib', 'skor' => '1.0', 'metode' => 'curated'],
    ]);
    echo "[OK] Lowongan ID 2254 '{$loker1->judul_pekerjaan}' disetel ke Published, Min. S1, 3 Skill.\n";
}

// 2. Kondisikan Lowongan 2: FRONT OFFICE (PT. Sumbermitra Asri Hotel, ID: 915)
$loker2 = ReqPkLoker::find(915);
if ($loker2) {
    $loker2->status_loker = 'published';
    $loker2->education_level_id = 3; // SMA / SMK
    $loker2->save();

    // Pastikan skills terhubung di lowongan_skills
    // Skill: 1382 (Terapkan Respons Pertama), 1804 (Layanan Pelanggan)
    DB::table('lowongan_skills')->where('vac_id', $loker2->vac_id)->delete();
    DB::table('lowongan_skills')->insert([
        ['vac_id' => $loker2->vac_id, 'esco_skill_id' => 1382, 'tipe_keahlian' => 'wajib', 'skor' => '1.0', 'metode' => 'curated'],
        ['vac_id' => $loker2->vac_id, 'esco_skill_id' => 1804, 'tipe_keahlian' => 'wajib', 'skor' => '1.0', 'metode' => 'curated'],
    ]);
    echo "[OK] Lowongan ID 915 '{$loker2->judul_pekerjaan}' disetel ke Published, Min. SMA, 2 Skill.\n";
}

echo "--------------------------------------------------------" . PHP_EOL;

// 3. KANDIDAT 1: USDAR (ID: 981339) -> MATCH 100% (S1 untuk Technical Account Manager)
$usdar = JobSeeker::find(981339);
if ($usdar) {
    $usdar->name = 'Usdar';
    $usdar->education_level_id = 5; // S1
    $usdar->save();

    // Hanya ubah skill
    $usdar->skills()->sync([
        8228 => ['is_manual' => false, 'source' => 'keahlian'],
        5029 => ['is_manual' => false, 'source' => 'experience'],
        7371 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "[OK] Kandidat 1: {$usdar->name} (ID: {$usdar->id}) -> S1 & 3 Skill.\n";
}

// 4. KANDIDAT 2: YUDA HANDI SUMATEJA (ID: 920682) -> MATCH 67% (S1, Gap Skill 7371)
$yuda = JobSeeker::find(920682);
if ($yuda) {
    $yuda->name = 'Yuda Handi Sumateja';
    $yuda->education_level_id = 5; // S1
    $yuda->save();

    // Hanya ubah skill
    $yuda->skills()->sync([
        8228 => ['is_manual' => false, 'source' => 'keahlian'],
        5029 => ['is_manual' => false, 'source' => 'experience'],
    ]);
    echo "[OK] Kandidat 2: {$yuda->name} (ID: {$yuda->id}) -> S1 & 2 Skill (Gap 1).\n";
}

// 5. KANDIDAT 3: I KOMANG TRI AGUSTIA (ID: 981338) -> MATCH 100% (SMA untuk Front Office)
$komang = JobSeeker::find(981338);
if ($komang) {
    $komang->name = 'I Komang Tri Agustia';
    $komang->education_level_id = 3; // SMA
    $komang->save();

    // Hanya ubah skill
    $komang->skills()->sync([
        1382 => ['is_manual' => false, 'source' => 'keahlian'],
        1804 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "[OK] Kandidat 3: {$komang->name} (ID: {$komang->id}) -> SMA & 2 Skill.\n";
}

// 6. KANDIDAT 4: ELSYA MONALISA PANE (ID: 922853) -> MATCH 50% (D3 >= SMA, Gap Skill 1382)
$elsya = JobSeeker::find(922853);
if ($elsya) {
    $elsya->name = 'Elsya Monalisa Pane';
    $elsya->education_level_id = 4; // D3
    $elsya->save();

    // Hanya ubah skill
    $elsya->skills()->sync([
        1804 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "[OK] Kandidat 4: {$elsya->name} (ID: {$elsya->id}) -> D3 & 1 Skill (Gap 1).\n";
}

// 7. KANDIDAT 5: RIZKY ANANDA (ID: 922854) -> UJI ELIMINASI PENDIDIKAN (SMA)
$rizky = JobSeeker::find(922854);
if ($rizky) {
    $rizky->name = 'Rizky Ananda';
    $rizky->education_level_id = 3; // SMA
    $rizky->save();

    // Skill lengkap untuk Account Manager tapi pendidikan SMA
    $rizky->skills()->sync([
        8228 => ['is_manual' => false, 'source' => 'keahlian'],
        5029 => ['is_manual' => false, 'source' => 'keahlian'],
        7371 => ['is_manual' => false, 'source' => 'keahlian'],
    ]);
    echo "[OK] Kandidat 5: {$rizky->name} (ID: {$rizky->id}) -> SMA & 3 Skill Account Manager.\n";
}

echo PHP_EOL . "=== VERIFIKASI PENGUJIAN API MATCHING ===" . PHP_EOL;

$matchingController = app(\App\Http\Controllers\Api\MatchingController::class);

// Test 1: USDAR
$req1 = \Illuminate\Http\Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => $usdar->id,
    'pekerjaan' => 'Account Manager',
    'skills' => [8228, 5029, 7371],
    'filter_pendidikan' => true,
]);
$res1 = json_decode($matchingController->recommendLowongan($req1)->getContent(), true);
$top1 = $res1['data'][0] ?? null;
echo sprintf("1. Usdar -> Lowongan: %s | Skor: %d%% | Status Edu: %s\n", 
    $top1['lowongan']['judul_pekerjaan'] ?? '-', 
    $top1['match_score'] ?? 0, 
    $top1['education_match']['label'] ?? '-'
);

// Test 2: YUDA
$req2 = \Illuminate\Http\Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => $yuda->id,
    'pekerjaan' => 'Account Manager',
    'skills' => [8228, 5029],
    'filter_pendidikan' => true,
]);
$res2 = json_decode($matchingController->recommendLowongan($req2)->getContent(), true);
$top2 = $res2['data'][0] ?? null;
echo sprintf("2. Yuda -> Lowongan: %s | Skor: %d%% | Status Edu: %s\n", 
    $top2['lowongan']['judul_pekerjaan'] ?? '-', 
    $top2['match_score'] ?? 0, 
    $top2['education_match']['label'] ?? '-'
);

// Test 3: KOMANG
$req3 = \Illuminate\Http\Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => $komang->id,
    'pekerjaan' => 'Front Office',
    'skills' => [1382, 1804],
    'filter_pendidikan' => true,
]);
$res3 = json_decode($matchingController->recommendLowongan($req3)->getContent(), true);
$top3 = $res3['data'][0] ?? null;
echo sprintf("3. Komang -> Lowongan: %s | Skor: %d%% | Status Edu: %s\n", 
    $top3['lowongan']['judul_pekerjaan'] ?? '-', 
    $top3['match_score'] ?? 0, 
    $top3['education_match']['label'] ?? '-'
);

// Test 4: ELSYA
$req4 = \Illuminate\Http\Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => $elsya->id,
    'pekerjaan' => 'Front Office',
    'skills' => [1804],
    'filter_pendidikan' => true,
]);
$res4 = json_decode($matchingController->recommendLowongan($req4)->getContent(), true);
$top4 = $res4['data'][0] ?? null;
echo sprintf("4. Elsya -> Lowongan: %s | Skor: %d%% | Status Edu: %s\n", 
    $top4['lowongan']['judul_pekerjaan'] ?? '-', 
    $top4['match_score'] ?? 0, 
    $top4['education_match']['label'] ?? '-'
);

// Test 5: RIZKY (Uji gugur)
$req5 = \Illuminate\Http\Request::create('/api/rekomendasi/lowongan', 'POST', [
    'pencaker_id' => $rizky->id,
    'pekerjaan' => 'Technical Account Manager',
    'skills' => [8228, 5029, 7371],
    'filter_pendidikan' => true,
]);
$res5 = json_decode($matchingController->recommendLowongan($req5)->getContent(), true);
$foundTechnicalAccount = false;
foreach ($res5['data'] as $r) {
    if ($r['lowongan']['id'] == 2254) {
        $foundTechnicalAccount = true;
    }
}
echo sprintf("5. Rizky (SMA) cari 'Technical Account Manager' (S1) -> Muncul lowongan S1? %s (Harus TIDAK)\n",
    $foundTechnicalAccount ? 'YA (SALAH)' : 'TIDAK (BENAR - GUGUR KARENA PENDIDIKAN DI BAWAH SYARAT)'
);

echo PHP_EOL . "=== SEEDING SELESAI DENGAN SUKSES! ===" . PHP_EOL;
