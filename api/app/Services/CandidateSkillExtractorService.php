<?php

namespace App\Services;

use App\Models\JobSeeker;
use App\Models\SkillNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CandidateSkillExtractorService
{
    /**
     * Stopwords / generic filler words to ignore during extraction
     */
    protected array $stopWords = [
        'dan', 'atau', 'di', 'ke', 'dari', 'yang', 'untuk', 'pada', 'dengan', 'adalah',
        'sebagai', 'seorang', 'staf', 'staff', 'karyawan', 'pegawai', 'pekerja',
        'magang', 'intern', 'internship', 'kerja', 'praktek', 'pkl', 'kkt', 'kkn',
        'kursus', 'pelatihan', 'sertifikat', 'sertifikasi', 'surat', 'tanda',
        'registrasi', 'str', 'kompetensi', 'ijazah', 'lulusan', 'tamatan',
        'mampu', 'dapat', 'bisa', 'ahli', 'terbiasa', 'menguasai', 'memahami',
        'aplikasi', 'software', 'program', 'alat', 'bidang', 'bagian', 'divisi',
        'dll', 'dsb', 'dst', 'baik', 'benar', 'secara', 'tingkat', 'level'
    ];

    /**
     * Ekstraksi skill ESCO secara otomatis untuk seorang pencari kerja
     * berdasarkan kombinasi: keahlian, experience, dan sertifikasi.
     */
    public function extractForJobSeeker(JobSeeker $jobSeeker, int $limitPerCategory = 5): array
    {
        $keywords = $this->extractAllKeywords($jobSeeker);

        $keahlianSkills = $this->searchSkillsForKeywords($keywords['keahlian'], 'keahlian', $limitPerCategory);
        $experienceSkills = $this->searchSkillsForKeywords($keywords['experience'], 'experience', $limitPerCategory);
        $sertifikasiSkills = $this->searchSkillsForKeywords($keywords['sertifikasi'], 'sertifikasi', $limitPerCategory);

        // Gabungkan dan hindari duplikasi, sertakan metadata asal sumber
        $mergedSkills = [];
        $seenIds = [];

        $addSkill = function ($skill, string $source) use (&$mergedSkills, &$seenIds) {
            $id = $skill->id;
            if (isset($seenIds[$id])) {
                // Tambahkan sumber jika skill yang sama terdeteksi dari sumber lain
                $existingIndex = $seenIds[$id];
                $currentSources = explode(', ', $mergedSkills[$existingIndex]['source'] ?? '');
                if (!in_array($source, $currentSources)) {
                    $mergedSkills[$existingIndex]['source'] .= ', ' . $source;
                }
                return;
            }

            $seenIds[$id] = count($mergedSkills);
            $mergedSkills[] = [
                'id' => $skill->id,
                'code' => $skill->code,
                'title' => $skill->title,
                'title_en' => $skill->title_en,
                'type' => $skill->type,
                'description' => $skill->description,
                'source' => $source,
                'is_manual' => false,
            ];
        };

        foreach ($keahlianSkills as $skill) {
            $addSkill($skill, 'keahlian');
        }

        foreach ($experienceSkills as $skill) {
            $addSkill($skill, 'experience');
        }

        foreach ($sertifikasiSkills as $skill) {
            $addSkill($skill, 'sertifikasi');
        }

        return [
            'skills' => $mergedSkills,
            'keywords' => $keywords,
            'counts' => [
                'total' => count($mergedSkills),
                'from_keahlian' => $keahlianSkills->count(),
                'from_experience' => $experienceSkills->count(),
                'from_sertifikasi' => $sertifikasiSkills->count(),
            ],
        ];
    }

    /**
     * Sinkronisasikan skill ke tabel pivot pencaker_esco_skills
     * Hanya timpa jika force = true atau belum ada skill manual.
     */
    public function syncSkillsForJobSeeker(JobSeeker $jobSeeker, bool $force = false): array
    {
        // Cek apakah kandidat sudah memiliki skill manual yang ditambahkan operator
        $hasManualSkills = $jobSeeker->skills()->wherePivot('is_manual', true)->exists();

        if ($hasManualSkills && !$force) {
            // Jika ada skill manual dan tidak dipaksa re-extract, jangan timpa
            return [
                'skills' => $jobSeeker->skills()->get()->toArray(),
                'message' => 'Skill manual dipertahankan.',
            ];
        }

        $extraction = $this->extractForJobSeeker($jobSeeker);
        $extractedSkills = $extraction['skills'];

        if (!empty($extractedSkills)) {
            $syncData = [];
            foreach ($extractedSkills as $s) {
                $syncData[$s['id']] = [
                    'is_manual' => false,
                    'source' => $s['source'] ?? 'keahlian',
                ];
            }

            $jobSeeker->skills()->sync($syncData);
            $jobSeeker->load(['skills' => function ($q) {
                $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
            }]);
        }

        return $extraction;
    }

    /**
     * Ekstraksi seluruh kata kunci dari ketiga kolom: keahlian, experience, sertifikasi
     */
    public function extractAllKeywords(JobSeeker $jobSeeker): array
    {
        return [
            'keahlian' => $this->extractKeahlianKeywords($jobSeeker->keahlian),
            'experience' => $this->extractExperienceKeywords($jobSeeker->experience),
            'sertifikasi' => $this->extractSertifikasiKeywords($jobSeeker->sertifikasi),
        ];
    }

    /**
     * Ekstraksi kata kunci dari kolom keahlian
     */
    public function extractKeahlianKeywords(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $keywords = [];
        // Pisahkan berdasarkan koma, titik koma, garis miring, garis tegak, atau enter
        $parts = preg_split('/[,;\/\|\n]+/u', $raw);

        foreach ($parts as $part) {
            $clean = trim($part);
            if (empty($clean)) {
                continue;
            }

            // Bersihkan pola "mampu menggunakan...", "menguasai...", dll.
            $clean = preg_replace('/^(mampu|dapat|bisa|terbiasa|menguasai|ahli|memahami)\s+(menggunakan|mengoperasikan|menjalankan|membuat|bekerja\s+dengan|dalam\s+hal)?\s*/ui', '', $clean);
            $clean = preg_replace('/^(aplikasi|software|program|keterampilan|skill)\s+/ui', '', $clean);
            $clean = trim($clean, " \t\n\r\0\x0B-.,");

            if (mb_strlen($clean) >= 3 && !$this->isPureStopword($clean)) {
                $keywords[] = $clean;
                
                // Jika mengandung aplikasi spesifik (misal: "Excel, Ms word, Myob")
                if (preg_match_all('/\b(excel|word|powerpoint|myob|abss|autocad|photoshop|corel|canva|python|java|php|sql|spss)\b/ui', $clean, $appMatches)) {
                    foreach ($appMatches[0] as $app) {
                        $keywords[] = ucfirst(strtolower($app));
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    /**
     * Ekstraksi kata kunci jabatan/peran dari kolom experience
     * Format umum: [Jabatan; Perusahaan; Periode;] | [Jabatan 2; ...]
     */
    public function extractExperienceKeywords(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $keywords = [];

        // 1. Tangkap blok dalam tanda kurung siku: [Posisi; Instansi; Periode;]
        if (preg_match_all('/\[([^;\]]+)(?:;[^\]]*)?\]/u', $raw, $matches)) {
            $rawRoles = $matches[1];
        } else {
            // Fallback jika tidak memakai format kurung siku
            $rawRoles = preg_split('/[\|\n;]+/u', $raw);
        }

        foreach ($rawRoles as $role) {
            $clean = trim($role);
            if (empty($clean)) {
                continue;
            }

            // Bersihkan awalan peran umum
            $clean = preg_replace('/^(sebagai\s+(seorang\s+)?|staf\s+|staff\s+|bagian\s+|divisi\s+|helper\s+)/ui', '', $clean);
            $clean = trim($clean, " \t\n\r\0\x0B-.,;");

            if (mb_strlen($clean) < 3 || $this->isPureStopword($clean)) {
                continue;
            }

            $keywords[] = $clean;

            // Mapping singkatan & domain industri populer
            $upper = strtoupper($clean);
            if (str_contains($upper, 'QC') || str_contains($upper, 'QUALITY CONTROL')) {
                $keywords[] = 'Quality Control';
            }
            if (str_contains($upper, 'MAINTENANCE') || str_contains($upper, 'TEKNISI') || str_contains($upper, 'TECHNICIAN')) {
                $keywords[] = 'Maintenance';
                $keywords[] = 'Teknisi';
            }
            if (str_contains($upper, 'ADMIN')) {
                $keywords[] = 'Administrasi';
            }
            if (str_contains($upper, 'GUDANG') || str_contains($upper, 'WAREHOUSE')) {
                $keywords[] = 'Gudang';
                $keywords[] = 'Pergudangan';
            }
            if (str_contains($upper, 'KASIR') || str_contains($upper, 'CASHIER')) {
                $keywords[] = 'Kasir';
            }
            if (str_contains($upper, 'WELDER') || str_contains($upper, 'LAS')) {
                $keywords[] = 'Welder';
                $keywords[] = 'Pengelasan';
            }
            if (str_contains($upper, 'TUTOR') || str_contains($upper, 'GURU') || str_contains($upper, 'PENGAJAR') || str_contains($upper, 'TENTOR')) {
                $keywords[] = 'Tutor';
                $keywords[] = 'Pengajaran';
            }
            if (str_contains($upper, 'PERAWAT') || str_contains($upper, 'NURSE')) {
                $keywords[] = 'Keperawatan';
            }
            if (str_contains($upper, 'AKUNTAN') || str_contains($upper, 'ACCOUNTING')) {
                $keywords[] = 'Akuntansi';
            }
            if (str_contains($upper, 'KEUANGAN') || str_contains($upper, 'FINANCE')) {
                $keywords[] = 'Keuangan';
            }
            if (str_contains($upper, 'SALES') || str_contains($upper, 'PENJUALAN') || str_contains($upper, 'PRAMUNIAGA')) {
                $keywords[] = 'Penjualan';
                $keywords[] = 'Pelayanan Pelanggan';
            }
            if (str_contains($upper, 'MARKETING') || str_contains($upper, 'PEMASARAN')) {
                $keywords[] = 'Pemasaran';
            }
            if (str_contains($upper, 'PRODUKSI') || str_contains($upper, 'OPERATOR')) {
                $keywords[] = 'Produksi';
                $keywords[] = 'Manufaktur';
            }
            if (str_contains($upper, 'DRIVER') || str_contains($upper, 'SOPIR') || str_contains($upper, 'PENGEMUDI')) {
                $keywords[] = 'Pengemudi';
                $keywords[] = 'Mengemudi';
            }
            if (str_contains($upper, 'SECURITY') || str_contains($upper, 'SATPAM') || str_contains($upper, 'KEAMANAN')) {
                $keywords[] = 'Keamanan';
                $keywords[] = 'Security';
            }
            if (str_contains($upper, 'KOKI') || str_contains($upper, 'COOK') || str_contains($upper, 'CHEF') || str_contains($upper, 'MEMASAK')) {
                $keywords[] = 'Memasak';
                $keywords[] = 'Kuliner';
            }

            // Tambahan kata turunan untuk multi-kata (misal: "Admin Gudang" -> "Gudang")
            $words = preg_split('/\s+/u', $clean);
            if (count($words) > 1) {
                foreach ($words as $w) {
                    $wClean = trim($w);
                    if (mb_strlen($wClean) >= 4 && !$this->isPureStopword($wClean)) {
                        $keywords[] = $wClean;
                    }
                }
            }

            // Jika peran majemuk (mis: "ADMINISTRASI UMUM DAN BAGIAN KEUANGAN")
            if (preg_match('/\b(dan|&|\/)\b/i', $clean)) {
                $subParts = preg_split('/\b(dan|&|\/)\b/ui', $clean);
                foreach ($subParts as $sub) {
                    $subClean = trim(preg_replace('/^(bagian|divisi|staf|staff)\s+/ui', '', trim($sub)));
                    if (mb_strlen($subClean) >= 3 && !$this->isPureStopword($subClean)) {
                        $keywords[] = $subClean;
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    /**
     * Ekstraksi kata kunci dari kolom sertifikasi
     */
    public function extractSertifikasiKeywords(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $keywords = [];
        $parts = preg_split('/[,;\/\|\n]+/u', $raw);

        foreach ($parts as $part) {
            $clean = trim($part);
            if (empty($clean)) {
                continue;
            }

            // Bersihkan awalan nama sertifikat standar
            $clean = preg_replace('/^(sertifikat\s+(kompetensi)?\s*|sertifikasi\s*|pelatihan\s*|program\s+pelatihan\s*|kursus\s*|uji\s+kemahiran\s*|test\s+of\s*|surat\s+tanda\s+registrasi\s*|surat\s+izin\s*|lisensi\s*|training\s*|certificate\s+(of)?\s*)/ui', '', $clean);
            $clean = preg_replace('/(\s+angkatan\s+\d+|\s+batch\s+\d+|\s+level\s+\d+|\s+tingkat\s+\w+)?$/ui', '', $clean);
            $clean = trim($clean, " \t\n\r\0\x0B-.,;");

            if (mb_strlen($clean) < 3 || $this->isPureStopword($clean)) {
                continue;
            }

            $keywords[] = $clean;

            // Mapping sertifikasi umum
            $upper = strtoupper($clean);
            if (str_contains($upper, 'TOEFL') || str_contains($upper, 'ENGLISH') || str_contains($upper, 'TOEIC') || str_contains($upper, 'IELTS')) {
                $keywords[] = 'English';
                $keywords[] = 'Bahasa Inggris';
            }
            if (str_contains($upper, 'BTCLS') || str_contains($upper, 'BLS') || str_contains($upper, 'ACLS') || str_contains($upper, 'GAWAT DARURAT')) {
                $keywords[] = 'Gawat Darurat';
                $keywords[] = 'Keperawatan';
            }
            if (str_contains($upper, 'K3') || str_contains($upper, 'KESELAMATAN')) {
                $keywords[] = 'Keselamatan Kerja';
                $keywords[] = 'K3';
            }
            if (str_contains($upper, 'BREVET') || str_contains($upper, 'PAJAK')) {
                $keywords[] = 'Perpajakan';
                $keywords[] = 'Pajak';
            }
            if (str_contains($upper, 'MICROSOFT') || str_contains($upper, 'OFFICE') || str_contains($upper, 'KOMPUTER')) {
                $keywords[] = 'Microsoft Office';
                $keywords[] = 'Komputer';
            }
            if (str_contains($upper, 'HOUSEKEEPER') || str_contains($upper, 'HOUSEKEEPING')) {
                $keywords[] = 'Tata Graha';
                $keywords[] = 'Housekeeping';
            }
            if (str_contains($upper, 'PUBLIC SPEAKING') || str_contains($upper, 'KOMUNIKASI')) {
                $keywords[] = 'Public Speaking';
                $keywords[] = 'Komunikasi';
            }
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    /**
     * Cari kecocokan di tabel skill_nodes berdasarkan daftar kata kunci
     */
    protected function searchSkillsForKeywords(array $keywords, string $source, int $limit = 5): Collection
    {
        if (empty($keywords)) {
            return collect();
        }

        $allMatched = collect();

        foreach ($keywords as $keyword) {
            $term = '%' . trim($keyword) . '%';
            $exactTerm = trim($keyword);

            // Cari skill dengan prioritas exact match, title match, dan type = 'skill'
            $nodes = SkillNode::where(function ($query) use ($term) {
                $query->where('title', 'ILIKE', $term)
                    ->orWhere('title_en', 'ILIKE', $term)
                    ->orWhereRaw("array_to_string(alt_labels, ' ') ILIKE ?", [$term])
                    ->orWhereRaw("array_to_string(alt_labels_en, ' ') ILIKE ?", [$term]);
            })
            ->select('id', 'code', 'title', 'title_en', 'type', 'description')
            ->orderByRaw("CASE 
                WHEN title ILIKE ? THEN 1 
                WHEN title_en ILIKE ? THEN 2
                WHEN type = 'skill' THEN 3
                ELSE 4 
            END", [$exactTerm, $exactTerm])
            ->limit(3)
            ->get();

            foreach ($nodes as $node) {
                if (!$allMatched->contains('id', $node->id)) {
                    $node->source = $source;
                    $allMatched->push($node);
                }
                if ($allMatched->count() >= $limit) {
                    break 2;
                }
            }
        }

        return $allMatched->slice(0, $limit);
    }

    /**
     * Memeriksa apakah suatu kata kunci adalah stopword belaka
     */
    protected function isPureStopword(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));
        return in_array($normalized, $this->stopWords, true);
    }
}
