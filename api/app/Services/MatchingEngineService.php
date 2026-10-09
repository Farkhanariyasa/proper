<?php

namespace App\Services;

use App\Models\JobSeeker;
use App\Models\LowonganKerja;
use App\Models\KbjiClassification;
use Illuminate\Support\Facades\DB;

class MatchingEngineService
{
    /**
     * Stopwords umum bahasa Indonesia untuk normalisasi teks
     */
    protected array $stopWords = [
        'dan', 'atau', 'di', 'ke', 'dari', 'yang', 'untuk', 'pada', 'dengan', 'adalah',
        'sebagai', 'seorang', 'staf', 'staff', 'karyawan', 'pegawai', 'pekerja',
        'magang', 'intern', 'internship', 'kerja', 'praktek', 'pkl', 'kursus',
        'pelatihan', 'sertifikat', 'sertifikasi', 'mampu', 'dapat', 'bisa', 'ahli',
        'terbiasa', 'menguasai', 'memahami', 'aplikasi', 'software', 'program',
        'alat', 'bidang', 'bagian', 'divisi', 'dll', 'dsb', 'dst', 'baik', 'benar',
        'secara', 'tingkat', 'level', 'minimal', 'maksimal', 'tahun', 'pengalaman',
        'pria', 'wanita', 'usia', 'pendidikan', 'jurusan', 'lulusan', 'tamatan'
    ];

    /**
     * Komputasi kesesuaian antara 1 kandidat pencari kerja dan 1 lowongan kerja
     * Menggunakan Multi-Criteria Weighted Matching Engine (Bebas Ketergantungan ID ESCO & KBJI)
     */
    public function matchPair(int $jobSeekerId, string $lowonganId): array
    {
        $lowongan = LowonganKerja::with(['kbji', 'educationLevel', 'province', 'regency', 'skills'])
            ->find($lowonganId);

        if (!$lowongan) {
            throw new \InvalidArgumentException('Lowongan kerja tidak ditemukan.');
        }

        $jobSeeker = JobSeeker::with(['educationLevel', 'regency.province', 'province', 'skills'])
            ->find($jobSeekerId);

        if (!$jobSeeker) {
            throw new \InvalidArgumentException('Profil pencari kerja tidak ditemukan.');
        }

        return $this->computeCompositeMatch($jobSeeker, $lowongan);
    }

    /**
     * Rekomendasi daftar lowongan yang paling cocok untuk seorang kandidat pencari kerja
     */
    public function recommendJobsForSeeker(int $jobSeekerId, array $filters = []): array
    {
        $jobSeeker = JobSeeker::with(['educationLevel', 'regency.province', 'province', 'skills'])
            ->find($jobSeekerId);

        if (!$jobSeeker) {
            throw new \InvalidArgumentException('Profil pencari kerja tidak ditemukan.');
        }

        // Ambil lowongan dari tabel req_pk_loker
        $query = LowonganKerja::query()
            ->with([
                'skills:id,title,title_en',
                'kbji:id,code,title',
                'educationLevel:id,name,sort_order',
                'province:id,name',
                'regency:id,name',
            ]);

        if (!empty($filters['status_lowongan'])) {
            $query->where('status_loker', strtolower($filters['status_lowongan']));
        } else {
            $query->published();
        }

        if (!empty($filters['provinsi_id'])) {
            $query->where('provinsi_id', $filters['provinsi_id']);
        }

        if (!empty($filters['tipe_pekerjaan'])) {
            $query->where('tipe_pekerjaan', 'ILIKE', '%' . $filters['tipe_pekerjaan'] . '%');
        }

        $vacancies = $query->latest('id')->take(50)->get();
        $recommendations = [];

        foreach ($vacancies as $job) {
            $matchResult = $this->computeCompositeMatch($jobSeeker, $job);

            // Jika kualifikasi pendidikan sangat jauh di bawah syarat mutlak, bisa dilewati atau diturunkan
            if (!$matchResult['education_match']['is_matched'] && ($matchResult['education_match']['status'] ?? '') === 'di_bawah_syarat') {
                // Berikan toleransi jika gap hanya 1 tingkat
                if (($matchResult['education_match']['score'] ?? 0) < 40) {
                    continue;
                }
            }

            $recommendations[] = [
                'job_id' => $job->id,
                'slug' => $job->slug,
                'judul_lowongan' => $job->judul_lowongan,
                'nama_perusahaan' => $job->nama_perusahaan,
                'tipe_pekerjaan' => $job->tipe_pekerjaan,
                'sistem_kerja' => $job->sistem_kerja,
                'kbji' => $job->kbji ? [
                    'id' => $job->kbji->id,
                    'code' => $job->kbji->code,
                    'title' => $job->kbji->title,
                ] : null,
                'education_level' => $job->educationLevel?->name,
                'location' => ($job->regency?->name ?? '') . ', ' . ($job->province?->name ?? ''),
                'gaji_tampilkan' => $job->gaji_tampilkan,
                'gaji_minimal' => $job->gaji_minimal,
                'gaji_maksimal' => $job->gaji_maksimal,
                'tanggal_tutup' => $job->tanggal_tutup?->toISOString(),
                'status_lowongan' => $job->status_lowongan,
                'score' => $matchResult['score'],
                'total_required' => $matchResult['total_required'],
                'total_matched' => $matchResult['total_matched'],
                'total_gap' => $matchResult['total_gap'],
                'classification' => $matchResult['classification'],
                'kbji_match' => $matchResult['kbji_match'],
                'education_match' => $matchResult['education_match'],
                'matched_skills' => $matchResult['matched_skills'],
                'gap_skills' => $matchResult['gap_skills'],
                'score_breakdown' => $matchResult['score_breakdown'] ?? null,
            ];
        }

        // Urutkan dari skor tertinggi ke terendah
        usort($recommendations, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $recommendations;
    }

    /**
     * Rekomendasi daftar kandidat pencari kerja yang paling cocok untuk sebuah lowongan
     */
    public function recommendCandidatesForJob(string $lowonganId, array $filters = []): array
    {
        $lowongan = LowonganKerja::with(['kbji', 'educationLevel', 'province', 'regency', 'skills'])
            ->find($lowonganId);

        if (!$lowongan) {
            throw new \InvalidArgumentException('Lowongan kerja tidak ditemukan.');
        }

        // Query kandidat dengan pencarian relevan & efisien (hindari memuat seluruh 900k baris sekaligus)
        $query = JobSeeker::query()
            ->with([
                'educationLevel:id,name,sort_order',
                'regency.province',
                'province',
                'skills',
            ]);

        if (!empty($filters['regency_id'])) {
            $query->where('regency_id', $filters['regency_id']);
        }

        // Filter kandidat yang memiliki profil bermakna (ada nama, keahlian, pengalaman, atau pendidikan)
        $query->where(function ($q) {
            $q->whereNotNull('keahlian')
              ->orWhereNotNull('experience')
              ->orWhereNotNull('sertifikasi');
        });

        // Ambil sampel relevan hingga 150 kandidat untuk dinilai
        $seekers = $query->latest('id')->take(150)->get();
        $recommendations = [];

        foreach ($seekers as $candidate) {
            $matchResult = $this->computeCompositeMatch($candidate, $lowongan);

            $recommendations[] = [
                'candidate_id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'nik' => $candidate->nik,
                'phone' => $candidate->phone,
                'gender' => $candidate->jenis_kelamin,
                'education_level' => $candidate->educationLevel?->name ?? $candidate->pendidikan,
                'study_field_group' => $candidate->jurusan,
                'experience_range' => $candidate->experience_range ?? null,
                'desired_occupation' => $candidate->desired_occupation,
                'kbji' => $candidate->kbji ? [
                    'id' => $candidate->kbji->id,
                    'code' => $candidate->kbji->code,
                    'title' => $candidate->kbji->title,
                ] : null,
                'location' => ($candidate->regency?->name ?? $candidate->kab_kota ?? '') . ', ' . ($candidate->province?->name ?? $candidate->provinsi ?? ''),
                'score' => $matchResult['score'],
                'total_required' => $matchResult['total_required'],
                'total_matched' => $matchResult['total_matched'],
                'total_gap' => $matchResult['total_gap'],
                'classification' => $matchResult['classification'],
                'kbji_match' => $matchResult['kbji_match'],
                'education_match' => $matchResult['education_match'],
                'matched_skills' => $matchResult['matched_skills'],
                'gap_skills' => $matchResult['gap_skills'],
                'score_breakdown' => $matchResult['score_breakdown'] ?? null,
            ];
        }

        // Urutkan dari skor tertinggi
        usort($recommendations, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($recommendations, 0, 50);
    }

    /**
     * Rekomendasi Terpadu Pasangan (Unified Pairwise Recommendations)
     */
    public function getUnifiedRecommendations(array $filters = []): array
    {
        // 1. Query Pencari Kerja (jika tidak ada filter pencaker spesifik, batasi ke sampel aktif terbaru)
        $seekerQuery = JobSeeker::query()
            ->with([
                'educationLevel:id,name,sort_order',
                'regency.province',
                'province',
                'skills',
            ]);

        if (!empty($filters['job_seeker_id'])) {
            $seekerQuery->where('id', $filters['job_seeker_id']);
        } else {
            // Ambil batch representatif yang memiliki keahlian terdata
            $seekerQuery->where(function ($q) {
                $q->whereNotNull('keahlian')
                  ->orWhereNotNull('experience');
            })->latest('id')->take(30);
        }

        $seekers = $seekerQuery->get();

        // 2. Query Lowongan Kerja
        $jobQuery = LowonganKerja::published()
            ->with([
                'skills:id,title,title_en',
                'kbji:id,code,title',
                'educationLevel:id,name,sort_order',
                'province:id,name',
                'regency:id,name',
            ]);

        if (!empty($filters['lowongan_id'])) {
            $jobQuery->where('id', $filters['lowongan_id']);
        }

        $vacancies = $jobQuery->get();

        $search = !empty($filters['search']) ? mb_strtolower(trim($filters['search'])) : null;
        $categoryFilter = !empty($filters['category']) ? $filters['category'] : null;
        $minScore = isset($filters['min_score']) && is_numeric($filters['min_score']) ? (int) $filters['min_score'] : null;

        $results = [];

        foreach ($seekers as $candidate) {
            foreach ($vacancies as $job) {
                // Filter pencarian teks jika diberikan
                if ($search) {
                    $matchedText = str_contains(mb_strtolower($candidate->full_name), $search)
                        || str_contains(mb_strtolower($candidate->nik), $search)
                        || str_contains(mb_strtolower($candidate->desired_occupation ?? ''), $search)
                        || str_contains(mb_strtolower($job->judul_lowongan), $search)
                        || str_contains(mb_strtolower($job->nama_perusahaan), $search);

                    if (!$matchedText) {
                        continue;
                    }
                }

                $matchResult = $this->computeCompositeMatch($candidate, $job);
                $score = $matchResult['score'];
                $classification = $matchResult['classification'];

                // Filter kategori jika diberikan
                if ($categoryFilter) {
                    if ($categoryFilter === 'ready' || $categoryFilter === 'high') {
                        if ($score < 65) {
                            continue;
                        }
                    } elseif ($classification['category'] !== $categoryFilter) {
                        continue;
                    }
                }

                // Filter minimal skor jika diberikan
                if ($minScore !== null && $score < $minScore) {
                    continue;
                }

                $results[] = [
                    'id' => $candidate->id . '_' . $job->id,
                    'score' => $score,
                    'total_required' => $matchResult['total_required'],
                    'total_matched' => $matchResult['total_matched'],
                    'total_gap' => $matchResult['total_gap'],
                    'classification' => $classification,
                    'kbji_match' => $matchResult['kbji_match'],
                    'education_match' => $matchResult['education_match'],
                    'score_breakdown' => $matchResult['score_breakdown'] ?? null,
                    'candidate' => [
                        'id' => $candidate->id,
                        'nik' => $candidate->nik,
                        'full_name' => $candidate->full_name,
                        'phone' => $candidate->phone,
                        'education_level' => $candidate->educationLevel?->name ?? $candidate->pendidikan,
                        'desired_occupation' => $candidate->desired_occupation,
                        'kbji' => $candidate->kbji ? [
                            'id' => $candidate->kbji->id,
                            'code' => $candidate->kbji->code,
                            'title' => $candidate->kbji->title,
                        ] : null,
                        'location' => ($candidate->regency?->name ?? $candidate->kab_kota ?? '') . ', ' . ($candidate->province?->name ?? $candidate->provinsi ?? ''),
                    ],
                    'job' => [
                        'id' => $job->id,
                        'slug' => $job->slug,
                        'judul_lowongan' => $job->judul_lowongan,
                        'nama_perusahaan' => $job->nama_perusahaan,
                        'tipe_pekerjaan' => $job->tipe_pekerjaan,
                        'sistem_kerja' => $job->sistem_kerja,
                        'kbji' => $job->kbji ? [
                            'id' => $job->kbji->id,
                            'code' => $job->kbji->code,
                            'title' => $job->kbji->title,
                        ] : null,
                        'education_level' => $job->educationLevel?->name,
                        'location' => ($job->regency?->name ?? '') . ', ' . ($job->province?->name ?? ''),
                        'gaji_tampilkan' => $job->gaji_tampilkan,
                        'gaji_minimal' => $job->gaji_minimal,
                        'gaji_maksimal' => $job->gaji_maksimal,
                    ],
                    'matched_skills' => $matchResult['matched_skills'],
                    'gap_skills' => $matchResult['gap_skills'],
                ];
            }
        }

        // Urutkan dari skor tertinggi
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $results;
    }

    /**
     * Algoritma Komputasi Multi-Criteria Weighted Match
     * Menggabungkan 5 Dimensi:
     * 1. Kesesuaian Jabatan / Peran (35%)
     * 2. Kesesuaian Keahlian & Kualifikasi (30%)
     * 3. Kesesuaian Pendidikan & Bidang Studi (20%)
     * 4. Kesesuaian Pengalaman Kerja (10%)
     * 5. Kesesuaian Lokasi & Sistem Kerja (5%)
     */
    public function computeCompositeMatch(JobSeeker $candidate, object $job): array
    {
        // 1. Evaluasi Dimensi Jabatan (Role) - Bobot 35%
        $roleMatch = $this->evaluateRoleMatch($candidate, $job);
        $roleScore = $roleMatch['score'];

        // 2. Evaluasi Dimensi Keahlian (Skills & Competencies) - Bobot 30%
        $skillMatch = $this->evaluateCompetencyMatch($candidate, $job);
        $skillScore = $skillMatch['score'];

        // 3. Evaluasi Dimensi Pendidikan (Education) - Bobot 20%
        $eduMatch = $this->evaluateEducationAndFieldMatch($candidate, $job);
        $eduScore = $eduMatch['score'];

        // 4. Evaluasi Dimensi Pengalaman (Experience) - Bobot 10%
        $expMatch = $this->evaluateExperienceMatch($candidate, $job);
        $expScore = $expMatch['score'];

        // 5. Evaluasi Dimensi Lokasi (Location) - Bobot 5%
        $locMatch = $this->evaluateLocationMatch($candidate, $job);
        $locScore = $locMatch['score'];

        // Hitung Komposit Skor Berbobot
        $totalWeightedScore = (int) round(
            (0.35 * $roleScore) +
            (0.30 * $skillScore) +
            (0.20 * $eduScore) +
            (0.10 * $expScore) +
            (0.05 * $locScore)
        );

        $totalWeightedScore = max(0, min(100, $totalWeightedScore));

        // Klasifikasi Skor
        $classification = $this->classifyScore($totalWeightedScore);

        // Jika jenjang pendidikan kandidat jauh di bawah syarat, berikan penanda diskualifikasi
        if (!$eduMatch['education_level_match']['is_matched'] && $eduMatch['education_level_match']['score'] < 30) {
            $classification = [
                'category' => 'disqualified',
                'label' => 'Pendidikan Tidak Memenuhi',
                'badge_color' => 'rose',
                'action_label' => 'Tidak Memenuhi Syarat',
                'action_key' => 'not_eligible_education',
                'description' => 'Jenjang pendidikan kandidat (' . ($candidate->educationLevel?->name ?? $candidate->pendidikan ?? 'Belum Terdata') . ') di bawah syarat minimal lowongan (' . ($job->educationLevel?->name ?? 'Min. Tertentu') . ').',
            ];
        }

        $matchedSkills = $skillMatch['matched_skills'];
        $gapSkills = $skillMatch['gap_skills'];
        $totalRequired = count($matchedSkills) + count($gapSkills);

        return [
            'candidate' => [
                'id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'nik' => $candidate->nik,
                'phone' => $candidate->phone,
                'education_level' => $candidate->educationLevel?->name ?? $candidate->pendidikan,
                'desired_occupation' => $candidate->desired_occupation,
                'kbji' => $candidate->kbji ? [
                    'code' => $candidate->kbji->code,
                    'title' => $candidate->kbji->title,
                ] : null,
                'location' => ($candidate->regency?->name ?? $candidate->kab_kota ?? '') . ', ' . ($candidate->province?->name ?? $candidate->provinsi ?? ''),
            ],
            'job' => [
                'id' => $job->id,
                'judul_lowongan' => $job->judul_lowongan,
                'nama_perusahaan' => $job->nama_perusahaan,
                'tipe_pekerjaan' => $job->tipe_pekerjaan,
                'sistem_kerja' => $job->sistem_kerja,
                'kbji' => $job->kbji ? [
                    'code' => $job->kbji->code,
                    'title' => $job->kbji->title,
                ] : null,
                'education_level' => $job->educationLevel?->name,
                'location' => ($job->regency?->name ?? '') . ', ' . ($job->province?->name ?? ''),
                'status_lowongan' => $job->status_lowongan,
            ],
            'score' => $totalWeightedScore,
            'total_required' => $totalRequired,
            'total_matched' => count($matchedSkills),
            'total_gap' => count($gapSkills),
            'classification' => $classification,
            'kbji_match' => $roleMatch['kbji_match'],
            'education_match' => $eduMatch['education_level_match'],
            'matched_skills' => $matchedSkills,
            'gap_skills' => $gapSkills,
            'score_breakdown' => [
                'role_score' => $roleScore,
                'skill_score' => $skillScore,
                'education_score' => $eduScore,
                'experience_score' => $expScore,
                'location_score' => $locScore,
            ],
        ];
    }

    /**
     * Dimensi 1: Evaluasi Kesesuaian Jabatan & Peran (Bobot 35%)
     * 100% JUDUL & DESKRIPSI FIRST:
     * Menilai kesesuaian MURNI dari Judul Lowongan & Deskripsi Pekerjaan
     * terhadap Target Okupasi, Riwayat Pekerjaan (Experience), dan Keahlian Kandidat.
     * KBJI TIDAK LAGI memengaruhi skor, hanya sebagai metadata informasional untuk UI.
     */
    protected function evaluateRoleMatch(JobSeeker $candidate, object $job): array
    {
        $jobTitle = $job->judul_lowongan ?? '';
        $jobDesc = $job->deskripsi_pekerjaan ?? '';
        $jobContext = mb_strtolower(trim($jobTitle . ' ' . $jobDesc));

        $candidateRole = $candidate->desired_occupation ?? '';
        $experienceText = $candidate->experience ?? '';

        // Kumpulkan kandidat peran dari desired_occupation dan riwayat kerja (experience)
        $candidateRoles = [];
        if (!empty($candidateRole)) {
            $candidateRoles[] = $candidateRole;
        }
        if (preg_match_all('/\[([^;\]]+)/', $experienceText, $matches)) {
            foreach ($matches[1] as $r) {
                $candidateRoles[] = trim($r);
            }
        }

        // Jika belum ada peran spesifik, gunakan jurusan atau keahlian utama sebagai proksi
        if (empty($candidateRoles)) {
            if (!empty($candidate->jurusan)) {
                $candidateRoles[] = $candidate->jurusan;
            }
            if (!empty($candidate->keahlian)) {
                $skills = $this->extractSkillsFromText($candidate->keahlian);
                if (!empty($skills)) {
                    $candidateRoles[] = $skills[0];
                }
            }
        }

        $bestScore = 15; // Baseline jika tidak ada kesamaan
        $jobTitleLower = mb_strtolower($jobTitle);
        $jobTitleTokens = $this->tokenizeText($jobTitle);
        $jobDescTokens = $this->tokenizeText($jobDesc);

        foreach ($candidateRoles as $role) {
            $roleLower = mb_strtolower($role);
            $roleTokens = $this->tokenizeText($role);

            if (empty($roleTokens)) continue;

            // Kasus 1: Judul persis atau substring lengkap (misal: "Frontend Developer" di "Senior Frontend Developer")
            if (str_contains($jobTitleLower, $roleLower) || str_contains($roleLower, $jobTitleLower)) {
                $bestScore = max($bestScore, 95);
                continue;
            }

            // Kasus 2: Peran kandidat disebut langsung di dalam deskripsi pekerjaan
            // (contoh: kandidat "Kasir", lowongan "Crew Outlet" tapi deskripsi ada "melayani transaksi kasir")
            if (str_contains($jobContext, $roleLower)) {
                $bestScore = max($bestScore, 85);
                continue;
            }

            // Kasus 3: Overlap kata kunci penting pada Judul Pekerjaan
            $commonTitleTokens = array_intersect($roleTokens, $jobTitleTokens);
            if (!empty($commonTitleTokens)) {
                $ratio = count($commonTitleTokens) / max(count($roleTokens), count($jobTitleTokens));
                $tokenScore = (int) round(60 + ($ratio * 35)); // Rentang 60 - 95%
                $bestScore = max($bestScore, $tokenScore);
                continue;
            }

            // Kasus 4: Overlap kata kunci peran dengan Deskripsi Pekerjaan
            $commonDescTokens = array_intersect($roleTokens, $jobDescTokens);
            if (!empty($commonDescTokens)) {
                $descRatio = count($commonDescTokens) / count($roleTokens);
                $tokenScore = (int) round(50 + ($descRatio * 30)); // Rentang 50 - 80%
                $bestScore = max($bestScore, $tokenScore);
                continue;
            }

            // Kasus 5: Kemiripan teks fuzzy Levenshtein / Dice
            $sim = $this->calculateTextSimilarity($jobTitle, $role);
            if ($sim >= 0.4) {
                $bestScore = max($bestScore, (int) round($sim * 100));
            }
        }

        // KBJI hanya sebagai metadata pendamping (tidak mendikte skor)
        $kbjiMatch = $this->evaluateKbjiMatch($candidate->kbji, $job->kbji);

        return [
            'score' => min(100, max(15, $bestScore)),
            'kbji_match' => $kbjiMatch,
            'title_similarity' => $bestScore / 100,
        ];
    }

    /**
     * Dimensi 2: Evaluasi Keahlian & Kualifikasi Profil (Bobot 30%)
     * Mengekstrak poin-poin kompetensi profil kandidat (keahlian, sertifikasi, experience)
     * dan mencocokkannya dengan kebutuhan lowongan secara tekstual & semantik.
     */
    protected function evaluateCompetencyMatch(JobSeeker $candidate, object $job): array
    {
        $rawProfileText = trim(
            ($candidate->keahlian ?? '') . ' ' .
            ($candidate->sertifikasi ?? '') . ' ' .
            ($candidate->experience ?? '')
        );

        $jobReqText = trim(
            ($job->judul_lowongan ?? '') . ' ' .
            ($job->deskripsi_pekerjaan ?? '') . ' ' .
            ($job->persyaratan_tambahan ?? '')
        );

        $matchedSkills = [];
        $gapSkills = [];

        // 1. Jika Lowongan memiliki relasi skills (misal dari inputan HR atau referensi)
        $jobSkills = $job->skills;
        $candidateEscoSkills = $candidate->skills;
        $candidateSkillIds = $candidateEscoSkills->pluck('id')->toArray();

        if ($jobSkills->isNotEmpty()) {
            foreach ($jobSkills as $reqSkill) {
                // Layer A: Cek exact ID / relasi taksonomi
                if (in_array($reqSkill->id, $candidateSkillIds)) {
                    $matchedSkills[] = [
                        'id' => $reqSkill->id,
                        'title' => $reqSkill->title,
                        'title_en' => $reqSkill->title_en,
                        'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                        'match_type' => 'Exact Match',
                        'matched_with' => $reqSkill->title,
                        'status_verifikasi' => 'Terverifikasi (Taksonomi)',
                    ];
                    continue;
                }

                // Layer B: Cek kemiripan teks nama skill di profil kandidat
                $skillTitle = mb_strtolower(trim($reqSkill->title));
                $skillTitleEn = mb_strtolower(trim($reqSkill->title_en ?? ''));

                if (
                    ($skillTitle !== '' && str_contains(mb_strtolower($rawProfileText), $skillTitle)) ||
                    ($skillTitleEn !== '' && str_contains(mb_strtolower($rawProfileText), $skillTitleEn))
                ) {
                    $matchedSkills[] = [
                        'id' => $reqSkill->id,
                        'title' => $reqSkill->title,
                        'title_en' => $reqSkill->title_en,
                        'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                        'match_type' => 'Padanan Teks Profil',
                        'matched_with' => $reqSkill->title,
                        'status_verifikasi' => 'Terpenuhi di CV/Profil',
                    ];
                } else {
                    // Cek kemiripan token kata kunci
                    $tokens = $this->tokenizeText($skillTitle);
                    $profileTokens = $this->tokenizeText($rawProfileText);
                    $common = array_intersect($tokens, $profileTokens);

                    if (count($common) >= 1 && (count($common) / max(1, count($tokens))) >= 0.5) {
                        $matchedSkills[] = [
                            'id' => $reqSkill->id,
                            'title' => $reqSkill->title,
                            'title_en' => $reqSkill->title_en,
                            'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                            'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                            'match_type' => 'Padanan Kata Kunci',
                            'matched_with' => implode(', ', $common),
                            'status_verifikasi' => 'Terpenuhi Sebagian',
                        ];
                    } else {
                        $gapSkills[] = [
                            'id' => $reqSkill->id,
                            'title' => $reqSkill->title,
                            'title_en' => $reqSkill->title_en,
                            'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                            'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                            'status' => 'missing_gap',
                        ];
                    }
                }
            }
        }

        // 2. Jika lowongan belum memiliki daftar skill terstruktur, ekstrak kata kunci kompetensi dari teks profil kandidat vs lowongan
        $candidateSkillItems = $this->extractSkillsFromText($candidate->keahlian ?? '');
        if (!empty($candidate->sertifikasi)) {
            $candidateSkillItems = array_merge($candidateSkillItems, $this->extractSkillsFromText($candidate->sertifikasi));
        }

        $jobReqLower = mb_strtolower($jobReqText);
        $jobReqTokens = $this->tokenizeText($jobReqText);
        $profileTokens = $this->tokenizeText($rawProfileText);

        $dummyId = 10000;
        foreach ($candidateSkillItems as $cSkill) {
            // Lewati jika sudah ada di matched
            $alreadyExists = false;
            foreach ($matchedSkills as $m) {
                if (mb_strtolower($m['title']) === mb_strtolower($cSkill)) {
                    $alreadyExists = true;
                    break;
                }
            }
            if ($alreadyExists) continue;

            $cSkillLower = mb_strtolower($cSkill);
            $cTokens = $this->tokenizeText($cSkill);
            $hasTokenMatch = false;
            $matchedWord = $cSkill;

            if (str_contains($jobReqLower, $cSkillLower)) {
                $hasTokenMatch = true;
            } else {
                foreach ($cTokens as $tok) {
                    if (mb_strlen($tok) >= 3 && in_array($tok, $jobReqTokens)) {
                        $hasTokenMatch = true;
                        $matchedWord = $tok;
                        break;
                    }
                }
            }

            if ($hasTokenMatch) {
                $matchedSkills[] = [
                    'id' => ++$dummyId,
                    'title' => $cSkill,
                    'title_en' => null,
                    'tipe_keahlian' => 'wajib',
                    'level_kemahiran' => 'menengah',
                    'match_type' => 'Keahlian Terpenuhi',
                    'matched_with' => $matchedWord,
                    'status_verifikasi' => 'Sesuai Deskripsi Pekerjaan',
                ];
            }
        }

        // 3. Ekstraksi potensi gap skills dari kata kunci penting lowongan jika gap_skills masih kosong
        if (empty($gapSkills) && count($matchedSkills) < 6) {
            $importantJobKeywords = array_filter($jobReqTokens, function($t) use ($profileTokens) {
                return mb_strlen($t) >= 4 && !in_array($t, $profileTokens);
            });

            $gapDummyId = 20000;
            foreach (array_slice(array_values(array_unique($importantJobKeywords)), 0, 4) as $gapWord) {
                $gapSkills[] = [
                    'id' => ++$gapDummyId,
                    'title' => ucwords($gapWord),
                    'title_en' => null,
                    'tipe_keahlian' => 'wajib',
                    'level_kemahiran' => 'menengah',
                    'status' => 'missing_gap',
                ];
            }
        }

        // 4. Perhitungan Skor Keahlian Komprehensif
        $commonTokenCount = count(array_intersect($profileTokens, $jobReqTokens));
        $reqTokenCount = max(1, count($jobReqTokens));
        $tokenCoverage = min(1.0, $commonTokenCount / min(15, $reqTokenCount));

        $totalItems = count($matchedSkills) + count($gapSkills);

        if ($totalItems > 0) {
            $itemRatioScore = (count($matchedSkills) / $totalItems) * 100;
            $skillScore = (int) round((0.65 * $itemRatioScore) + (0.35 * ($tokenCoverage * 100)));
        } else {
            $skillScore = (int) round($tokenCoverage * 100);
            if ($skillScore === 0 && !empty($candidate->keahlian)) {
                $skillScore = 30; // Baseline bila memiliki keahlian terisi
            }
        }

        return [
            'score' => min(100, max(15, $skillScore)),
            'matched_skills' => $matchedSkills,
            'gap_skills' => $gapSkills,
        ];
    }

    /**
     * Dimensi 3: Evaluasi Kesesuaian Pendidikan & Bidang Studi (Bobot 20%)
     */
    protected function evaluateEducationAndFieldMatch(JobSeeker $candidate, object $job): array
    {
        $eduLevelMatch = $this->evaluateEducationMatch($candidate->educationLevel, $job->educationLevel);
        $levelScore = $eduLevelMatch['score'];

        // Kesesuaian Jurusan
        $jobField = trim($job->jurusan_studi ?? '');
        $candField = trim($candidate->jurusan ?? '');

        if ($jobField === '' || mb_strtolower($jobField) === 'semua jurusan' || mb_strtolower($jobField) === 'semua') {
            $fieldScore = 100;
        } elseif ($candField === '') {
            $fieldScore = 50;
        } else {
            $sim = $this->calculateTextSimilarity($jobField, $candField);
            if (str_contains(mb_strtolower($candField), mb_strtolower($jobField)) || str_contains(mb_strtolower($jobField), mb_strtolower($candField))) {
                $fieldScore = 100;
            } elseif ($sim >= 0.5) {
                $fieldScore = 85;
            } else {
                $fieldScore = 40;
            }
        }

        $compositeEduScore = (int) round((0.6 * $levelScore) + (0.4 * $fieldScore));

        return [
            'score' => $compositeEduScore,
            'education_level_match' => $eduLevelMatch,
            'field_score' => $fieldScore,
        ];
    }

    /**
     * Dimensi 4: Evaluasi Kesesuaian Pengalaman Kerja (Bobot 10%)
     */
    protected function evaluateExperienceMatch(JobSeeker $candidate, object $job): array
    {
        $reqYears = $job->pengalaman_minimal_tahun ?? 0;

        if ($reqYears <= 0) {
            return ['score' => 100, 'label' => 'Terbuka Fresh Graduate'];
        }

        // Estimasi tahun pengalaman kandidat
        $candYears = 0;
        $expText = $candidate->experience ?? '';

        if (!empty($candidate->experience_range)) {
            $candYears = match ($candidate->experience_range) {
                'fresh_graduate' => 0,
                '<1' => 0.5,
                '1-3' => 2,
                '3-5' => 4,
                '>5' => 6,
                default => 1,
            };
        } elseif (!empty($expText)) {
            // Hitung jumlah baris/record riwayat kerja
            $count = substr_count($expText, ';');
            $candYears = max(1, $count);
        }

        if ($candYears >= $reqYears) {
            $score = 100;
        } else {
            $gap = $reqYears - $candYears;
            $score = max(20, (int) round(100 - ($gap * 25)));
        }

        return [
            'score' => $score,
            'required_years' => $reqYears,
            'candidate_years' => $candYears,
        ];
    }

    /**
     * Dimensi 5: Evaluasi Kesesuaian Lokasi & Sistem Kerja (Bobot 5%)
     */
    protected function evaluateLocationMatch(JobSeeker $candidate, object $job): array
    {
        $workSystem = mb_strtolower($job->sistem_kerja ?? '');

        // Jika WFH atau Remote, lokasi tidak membatasi
        if ($workSystem === 'remote' || $workSystem === 'wfh') {
            return ['score' => 100, 'label' => 'Fleksibel (Remote/WFH)'];
        }

        $cReg = $candidate->regency_id;
        $jReg = $job->regency_id;

        if ($cReg && $jReg && $cReg === $jReg) {
            return ['score' => 100, 'label' => 'Kabupaten/Kota Sama'];
        }

        $cProv = $candidate->province_id;
        $jProv = $job->provinsi_id;

        if ($cProv && $jProv && $cProv === $jProv) {
            return ['score' => 70, 'label' => 'Provinsi Sama'];
        }

        return ['score' => 35, 'label' => 'Luar Wilayah'];
    }

    /**
     * Evaluasi kesesuaian tingkat pendidikan antara kandidat dan lowongan kerja
     */
    public function evaluateEducationMatch($candidateEdu, $jobEdu): array
    {
        $cLevel = is_object($candidateEdu) ? ($candidateEdu->sort_order ?? $candidateEdu->id ?? null) : (is_numeric($candidateEdu) ? (int)$candidateEdu : null);
        $jLevel = is_object($jobEdu) ? ($jobEdu->sort_order ?? $jobEdu->id ?? null) : (is_numeric($jobEdu) ? (int)$jobEdu : null);

        $cName = is_object($candidateEdu) ? ($candidateEdu->name ?? '-') : (is_string($candidateEdu) && $candidateEdu !== '' ? $candidateEdu : '-');
        $jName = is_object($jobEdu) ? ($jobEdu->name ?? 'Semua Jenjang') : (is_string($jobEdu) && $jobEdu !== '' ? $jobEdu : 'Semua Jenjang');

        if (!$jLevel) {
            return [
                'is_matched' => true,
                'status' => 'semua_jenjang',
                'label' => 'Terbuka Semua Jenjang',
                'score' => 100,
                'required_level' => 'Semua Jenjang',
                'candidate_level' => $cName !== '-' ? $cName : 'Belum Ditentukan',
            ];
        }

        if (!$cLevel) {
            return [
                'is_matched' => true,
                'status' => 'belum_diisi',
                'label' => 'Pendidikan Belum Terdata',
                'score' => 60,
                'required_level' => $jName,
                'candidate_level' => 'Belum Ditentukan',
            ];
        }

        if ($cLevel >= $jLevel) {
            return [
                'is_matched' => true,
                'status' => 'sesuai',
                'label' => 'Memenuhi Syarat (' . $cName . ')',
                'score' => 100,
                'required_level' => $jName,
                'candidate_level' => $cName,
            ];
        }

        $gap = $jLevel - $cLevel;
        return [
            'is_matched' => $gap <= 1, // Toleransi 1 tingkat masih diizinkan
            'status' => 'di_bawah_syarat',
            'label' => 'Di Bawah Syarat (Butuh Min. ' . $jName . ')',
            'score' => max(10, 100 - ($gap * 35)),
            'required_level' => $jName,
            'candidate_level' => $cName,
        ];
    }

    /**
     * Mengevaluasi kesesuaian KBJI (Klasifikasi Baku Jabatan Indonesia)
     * Sekarang bersifat SOFT COMPATIBILITY (Bukan Hard Gatekeeper)
     */
    public function evaluateKbjiMatch(?object $candidateKbji, ?object $jobKbji): array
    {
        if (!$candidateKbji || !$jobKbji) {
            return [
                'is_compatible' => true,
                'match_level' => 'unspecified',
                'label' => 'KBJI Fleksibel',
                'code' => null,
                'compatibility_score' => 60,
            ];
        }

        $cCode = trim($candidateKbji->code);
        $jCode = trim($jobKbji->code);

        // Level 1: Jabatan Identik (exact) - Kode KBJI sama persis
        if ($cCode === $jCode) {
            return [
                'is_compatible' => true,
                'match_level' => 'exact',
                'label' => 'Jabatan Identik',
                'code' => $cCode,
                'compatibility_score' => 100,
            ];
        }

        // Level 2: Sub-Golongan Identik (unit_group) - 4 digit pertama sama
        $cUnit = substr($cCode, 0, 4);
        $jUnit = substr($jCode, 0, 4);
        if (strlen($cUnit) === 4 && strlen($jUnit) === 4 && $cUnit === $jUnit) {
            return [
                'is_compatible' => true,
                'match_level' => 'unit_group',
                'label' => 'Sub-Golongan Identik (' . $cUnit . ')',
                'code' => $cUnit,
                'compatibility_score' => 85,
            ];
        }

        // Level 3: Golongan Pokok Identik - 2 digit pertama sama
        $cMajor = substr($cCode, 0, 2);
        $jMajor = substr($jCode, 0, 2);
        if (strlen($cMajor) === 2 && strlen($jMajor) === 2 && $cMajor === $jMajor) {
            return [
                'is_compatible' => true,
                'match_level' => 'sub_major',
                'label' => 'Golongan Pokok Serumpun (' . $cMajor . ')',
                'code' => $cMajor,
                'compatibility_score' => 60,
            ];
        }

        // Berbeda sub-golongan: tetap kompatibel secara lunak, jangan dibuang
        return [
            'is_compatible' => true,
            'match_level' => 'different_group',
            'label' => 'Lintas Bidang Jabatan',
            'code' => $cCode . ' vs ' . $jCode,
            'compatibility_score' => 30,
        ];
    }

    /**
     * Standar Aturan Keputusan (Decision Rules Sesuai Rencana Awal)
     * 1. Perfect Match (100%)
     * 2. High Match (70% - 99%)
     * 3. Gap Match (40% - 69%)
     * 4. Low Match (< 40%)
     */
    public function classifyScore(int $score): array
    {
        if ($score >= 100) {
            return [
                'category' => 'perfect',
                'label' => '100% Siap Kerja (Perfect Match)',
                'badge_color' => 'emerald',
                'action_label' => 'Rekomendasikan ke Perusahaan',
                'action_key' => 'recommend_company',
                'description' => 'Seluruh kualifikasi dan kompetensi lowongan telah dipenuhi pelamar.',
            ];
        } elseif ($score >= 70) {
            return [
                'category' => 'high',
                'label' => 'Siap Ditempatkan Langsung (High Match)',
                'badge_color' => 'blue',
                'action_label' => 'Rekomendasikan ke Perusahaan',
                'action_key' => 'recommend_company',
                'description' => 'Memenuhi sebagian besar kompetensi dan kualifikasi yang disyaratkan.',
            ];
        } elseif ($score >= 40) {
            return [
                'category' => 'gap',
                'label' => 'Perlu Pelatihan Skill Gap (Gap Match)',
                'badge_color' => 'amber',
                'action_label' => 'Lihat Detail Gap',
                'action_key' => 'skill_gap_details',
                'description' => 'Memiliki modal dasar, namun terdapat kesenjangan skill yang perlu ditingkatkan.',
            ];
        } else {
            return [
                'category' => 'low',
                'label' => 'Belum Sesuai (Low Match)',
                'badge_color' => 'slate',
                'action_label' => 'Bimbingan Karir Dasar',
                'action_key' => 'career_guidance',
                'description' => 'Kesesuaian kualifikasi masih rendah untuk formasi lowongan kerja ini.',
            ];
        }
    }

    /**
     * Menghitung nilai kemiripan teks (0.0 - 1.0) menggunakan tokenisasi & Jaccard/Dice overlap
     */
    public function calculateTextSimilarity(string $textA, string $textB): float
    {
        $tokensA = $this->tokenizeText($textA);
        $tokensB = $this->tokenizeText($textB);

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        // Substring direct check
        $cleanA = implode(' ', $tokensA);
        $cleanB = implode(' ', $tokensB);
        if (str_contains($cleanA, $cleanB) || str_contains($cleanB, $cleanA)) {
            return 0.85;
        }

        // Token intersection
        $intersection = array_intersect($tokensA, $tokensB);
        $union = array_unique(array_merge($tokensA, $tokensB));

        if (empty($union)) {
            return 0.0;
        }

        // Jaccard similarity
        $jaccard = count($intersection) / count($union);

        // Dice coefficient (lebih memberi reward pada irisan yang cocok)
        $dice = (2 * count($intersection)) / (count($tokensA) + count($tokensB));

        return max($jaccard, $dice);
    }

    /**
     * Tokenisasi teks dan eliminasi stopwords
     */
    public function tokenizeText(string $text): array
    {
        $clean = mb_strtolower(trim($text));
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        $filtered = [];
        foreach ($words as $word) {
            if (mb_strlen($word) >= 2 && !in_array($word, $this->stopWords)) {
                $filtered[] = $word;
            }
        }

        return array_values(array_unique($filtered));
    }

    /**
     * Ekstraksi item keahlian dari teks bebas (pemisah koma, titik dua, garis baru)
     */
    public function extractSkillsFromText(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }

        $parts = preg_split('/[,;\n\r]+/', $text);
        $skills = [];

        foreach ($parts as $part) {
            $cleaned = trim(preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $part));
            if (mb_strlen($cleaned) >= 2 && !in_array(mb_strtolower($cleaned), $this->stopWords)) {
                $skills[] = ucwords(mb_strtolower($cleaned));
            }
        }

        return array_values(array_unique($skills));
    }
}
