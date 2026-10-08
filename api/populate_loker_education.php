<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MENGISI EDUCATION_LEVEL_ID PADA REQ_PK_LOKER ===" . PHP_EOL;

$eduLevels = DB::table('education_levels')->get()->keyBy('id');

// Batch process
$lokers = DB::table('req_pk_loker')
    ->leftJoin('kbji_classifications_2026', 'req_pk_loker.kbji_2026_id', '=', 'kbji_classifications_2026.id')
    ->select('req_pk_loker.id', 'req_pk_loker.judul_pekerjaan', 'req_pk_loker.deskripsi_pekerjaan', 'kbji_classifications_2026.code as kbji_code')
    ->get();

$updatedFromDesc = 0;
$updatedFromKbji = 0;
$updatedDefault = 0;

$updates = [];

foreach ($lokers as $loker) {
    $desc = strip_tags(strtolower($loker->deskripsi_pekerjaan ?? ''));
    $levelId = null;

    // 1. Khusus ID demo terverifikasi
    if ($loker->id == 2254) {
        $levelId = 5; // S1 untuk Technical Account Manager
    } elseif ($loker->id == 915) {
        $levelId = 3; // SMA/SMK untuk Front Office
    }

    // 2. Cek teks deskripsi pekerjaan
    if (!$levelId) {
        if (preg_match('/\b(s3|doktor|doktoral|ph\.?d)\b/i', $desc)) {
            $levelId = 7;
        } elseif (preg_match('/\b(s2|magister|master)\b/i', $desc)) {
            $levelId = 6;
        } elseif (preg_match('/\b(s1|s-1|sarjana|d4|d-4|bachelor)\b/i', $desc)) {
            $levelId = 5;
        } elseif (preg_match('/\b(d3|d-3|diploma|d1|d2|ahli madya)\b/i', $desc)) {
            $levelId = 4;
        } elseif (preg_match('/\b(sma|smk|slta|stm|sederajat|high school)\b/i', $desc)) {
            $levelId = 3;
        } elseif (preg_match('/\b(smp|sltp|junior high)\b/i', $desc)) {
            $levelId = 2;
        } elseif (preg_match('/\b(sd|sekolah dasar)\b/i', $desc)) {
            $levelId = 1;
        }

        if ($levelId) {
            $updatedFromDesc++;
        }
    }

    // 3. Fallback inferensi dari golongan KBJI
    if (!$levelId && !empty($loker->kbji_code)) {
        $firstDigit = substr(trim($loker->kbji_code), 0, 1);
        if (in_array($firstDigit, ['1', '2'])) {
            $levelId = 5; // Manajer / Profesional -> Min. S1
        } elseif ($firstDigit === '3') {
            $levelId = 4; // Teknisi / Asisten Profesional -> Min. D3
        } elseif (in_array($firstDigit, ['4', '5', '6', '7', '8'])) {
            $levelId = 3; // Tata Usaha, Jasa, Pertanian, Operator, Pengrajin -> Min. SMA/SMK
        } elseif ($firstDigit === '9') {
            $levelId = 2; // Pekerja Kasar -> Min. SMP
        }

        if ($levelId) {
            $updatedFromKbji++;
        }
    }

    // 4. Default umum di pasar tenaga kerja Indonesia
    if (!$levelId) {
        $levelId = 3; // SMA/SMK
        $updatedDefault++;
    }

    $updates[$levelId][] = $loker->id;
}

foreach ($updates as $lvlId => $ids) {
    foreach (array_chunk($ids, 500) as $chunk) {
        DB::table('req_pk_loker')->whereIn('id', $chunk)->update(['education_level_id' => $lvlId]);
    }
    $lvlName = $eduLevels[$lvlId]->name ?? "Level $lvlId";
    echo "Level {$lvlId} ({$lvlName}): " . count($ids) . " lowongan\n";
}

echo "Total terdeteksi dari deskripsi: $updatedFromDesc\n";
echo "Total terpetakan dari KBJI: $updatedFromKbji\n";
echo "Total default (SMA/SMK): $updatedDefault\n";
echo "Selesai mengisi education_level_id pada req_pk_loker!" . PHP_EOL;
