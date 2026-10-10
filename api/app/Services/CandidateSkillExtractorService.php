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
     * Ekstraksi skill murni dan autentik dari profil kandidat
     * Berdasarkan 3 pilar: keahlian, experience, dan sertifikasi (Bebas Taksonomi ESCO).
     */
    public function extractForJobSeeker(JobSeeker $jobSeeker, int $limitPerCategory = 10): array
    {
        $keywords = $this->extractAllKeywords($jobSeeker);

        $mergedSkills = [];
        $seenTitles = [];
        $dummyId = 2000;

        $addSkill = function (string $title, string $source, bool $isManual = false) use (&$mergedSkills, &$seenTitles, &$dummyId) {
            $cleanTitle = trim($title);
            $lower = mb_strtolower($cleanTitle);
            if (mb_strlen($cleanTitle) < 2 || isset($seenTitles[$lower])) {
                return;
            }

            // Cari HANYA jika ada exact match di skill_nodes, jangan pakai wildcard fuzzy '%...%'
            $node = SkillNode::whereRaw('LOWER(title) = ?', [$lower])
                ->orWhereRaw('LOWER(title_en) = ?', [$lower])
                ->first();

            $seenTitles[$lower] = true;
            $mergedSkills[] = [
                'id' => $node ? $node->id : ++$dummyId,
                'code' => $node?->code ?? null,
                'title' => $cleanTitle,
                'title_en' => $node?->title_en ?? null,
                'type' => $node?->type ?? 'skill',
                'description' => $node?->description ?? null,
                'source' => $source,
                'is_manual' => $isManual,
            ];
        };

        // 1. Ekstraksi langsung dari kolom Keahlian riil
        foreach (array_slice($keywords['keahlian'], 0, $limitPerCategory) as $k) {
            $addSkill($k, 'keahlian');
        }

        // 2. Ekstraksi langsung dari pengalaman kerja riil
        foreach (array_slice($keywords['experience'], 0, $limitPerCategory) as $e) {
            $addSkill($e, 'experience');
        }

        // 3. Ekstraksi langsung dari sertifikasi riil
        foreach (array_slice($keywords['sertifikasi'], 0, $limitPerCategory) as $s) {
            $addSkill($s, 'sertifikasi');
        }

        // 4. Sertakan keahlian manual yang pernah ditambahkan operator
        try {
            $manualSkills = $jobSeeker->skills()->wherePivot('is_manual', true)->get();
            foreach ($manualSkills as $ms) {
                $addSkill($ms->title, 'manual', true);
            }
        } catch (\Throwable $e) {
        }

        // 5. Anti-Redundansi: Bersihkan pecahan kata tunggal jika sudah tercakup dalam frasa lengkap
        // (contoh: hapus 'Technical', 'Support', 'Client', 'Service' jika sudah ada 'Technical Support & Client Service')
        $multiWordPhrases = [];
        foreach ($mergedSkills as $s) {
            $words = preg_split('/\s+/u', trim($s['title']));
            if (count($words) >= 2) {
                $multiWordPhrases[] = mb_strtolower($s['title']);
            }
        }

        if (!empty($multiWordPhrases)) {
            $cleanedMerged = [];
            foreach ($mergedSkills as $s) {
                $titleWords = preg_split('/\s+/u', trim($s['title']));
                $lowerTitle = mb_strtolower(trim($s['title']));

                // Jika hanya 1 kata, cek apakah kata ini merupakan pecahan dari frasa yang lebih lengkap
                if (count($titleWords) === 1) {
                    $isRedundantFragment = false;
                    foreach ($multiWordPhrases as $phrase) {
                        if (preg_match('/\b' . preg_quote($lowerTitle, '/') . '\b/iu', $phrase)) {
                            $isRedundantFragment = true;
                            break;
                        }
                    }
                    if ($isRedundantFragment) {
                        continue; // Lewati karena redundan
                    }
                }

                $cleanedMerged[] = $s;
            }
            $mergedSkills = $cleanedMerged;
        }

        return [
            'skills' => $mergedSkills,
            'keywords' => $keywords,
            'counts' => [
                'total' => count($mergedSkills),
                'from_keahlian' => count(array_filter($mergedSkills, fn($s) => $s['source'] === 'keahlian')),
                'from_experience' => count(array_filter($mergedSkills, fn($s) => $s['source'] === 'experience')),
                'from_sertifikasi' => count(array_filter($mergedSkills, fn($s) => $s['source'] === 'sertifikasi')),
            ],
        ];
    }

    /**
     * Sinkronisasikan skill kandidat ke tabel pivot pencaker_esco_skills
     */
    public function syncSkillsForJobSeeker(JobSeeker $jobSeeker, bool $force = false): array
    {
        $extraction = $this->extractForJobSeeker($jobSeeker);
        $extractedSkills = $extraction['skills'];

        if (!empty($extractedSkills)) {
            $syncData = [];
            foreach ($extractedSkills as $s) {
                $nodeExists = SkillNode::where('id', $s['id'])->exists();
                if ($nodeExists) {
                    $syncData[$s['id']] = [
                        'is_manual' => $s['is_manual'] ?? false,
                        'source' => $s['source'] ?? 'keahlian',
                    ];
                }
            }

            if (!empty($syncData)) {
                $jobSeeker->skills()->sync($syncData);
            }
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
            if (str_contains($upper, 'K3') || str_contains($upper, 'HSE') || str_contains($upper, 'SAFETY') || str_contains($upper, 'EHS')) {
                $keywords[] = 'K3 (Keselamatan Kerja)';
                $keywords[] = 'HSE';
            }
            if (str_contains($upper, 'HRD') || str_contains($upper, 'RECRUITER') || str_contains($upper, 'PERSONALIA') || str_contains($upper, 'HUMAN RESOURCE')) {
                $keywords[] = 'Human Resources (HR)';
                $keywords[] = 'Rekrutmen';
            }
            if (str_contains($upper, 'BARISTA') || str_contains($upper, 'BARTENDER') || str_contains($upper, 'WAITRESS') || str_contains($upper, 'WAITER') || str_contains($upper, 'F&B')) {
                $keywords[] = 'Food & Beverage (F&B)';
                $keywords[] = 'Pelayanan Pelanggan';
            }
            if (str_contains($upper, 'DESAIN') || str_contains($upper, 'DESIGN') || str_contains($upper, 'ILLUSTRATOR') || str_contains($upper, 'PHOTOSHOP')) {
                $keywords[] = 'Desain Grafis';
            }
            if (str_contains($upper, 'LEGAL') || str_contains($upper, 'HUKUM')) {
                $keywords[] = 'Legal & Kepatuhan';
            }
            if (str_contains($upper, 'PURCHASING') || str_contains($upper, 'BUYER') || str_contains($upper, 'PROCUREMENT') || str_contains($upper, 'PENGADAAN')) {
                $keywords[] = 'Pengadaan (Procurement)';
            }
            if (str_contains($upper, 'EKSPOR') || str_contains($upper, 'IMPOR') || str_contains($upper, 'EXPORT') || str_contains($upper, 'IMPORT') || str_contains($upper, 'CUSTOMS')) {
                $keywords[] = 'Ekspor Impor';
                $keywords[] = 'Perdagangan Internasional';
            }
            if (str_contains($upper, 'DIGITAL MARKETING') || str_contains($upper, 'SEO') || str_contains($upper, 'SOCIAL MEDIA') || str_contains($upper, 'SOSMED')) {
                $keywords[] = 'Digital Marketing';
            }
            if (str_contains($upper, 'LISTRIK') || str_contains($upper, 'ELEKTRO') || str_contains($upper, 'ELECTRIC')) {
                $keywords[] = 'Kelistrikan';
                $keywords[] = 'Teknik Elektro';
            }
            if (str_contains($upper, 'SIPIL') || str_contains($upper, 'KONSTRUKSI') || str_contains($upper, 'SURVEYOR')) {
                $keywords[] = 'Teknik Sipil';
                $keywords[] = 'Konstruksi';
            }
            if (str_contains($upper, 'MEKANIK') || str_contains($upper, 'OTOMOTIF')) {
                $keywords[] = 'Teknik Otomotif';
                $keywords[] = 'Mekanik';
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
