<?php

namespace App\Services;

use App\Models\JobSeeker;
use App\Models\LowonganKerja;
use App\Models\KbjiClassification;
use Illuminate\Support\Facades\DB;

class MatchingEngineService
{
    /**
     * Cache relasi taksonomi hierarki untuk mempercepat pencocokan batch
     */
    protected array $hierarchyCache = [];

    /**
     * Komputasi kesesuaian antara 1 kandidat pencari kerja dan 1 lowongan kerja
     * Mengimplementasikan Diagram Alur:
     * Exact ID -> Taxonomy Hierarchy -> Lexical / Substring / Token Match
     */
    public function matchPair(int $jobSeekerId, string $lowonganId): array
    {
        $lowongan = LowonganKerja::with(['kbji', 'educationLevel', 'province', 'regency'])
            ->find($lowonganId);

        if (!$lowongan) {
            throw new \InvalidArgumentException('Lowongan kerja tidak ditemukan.');
        }

        $jobSeeker = JobSeeker::with(['educationLevel', 'regency.province', 'kbji:id,code,title'])
            ->find($jobSeekerId);

        if (!$jobSeeker) {
            throw new \InvalidArgumentException('Profil pencari kerja tidak ditemukan.');
        }

        // 1. Evaluasi kesesuaian jabatan KBJI
        $kbjiMatch = $this->evaluateKbjiMatch($jobSeeker->kbji, $lowongan->kbji);

        // 2. Muat skill yang disyaratkan oleh lowongan (S_required)
        $requiredSkills = $lowongan->skills;
        $totalRequired = $requiredSkills->count();

        // 3. Muat skill yang dimiliki pelamar (dengan metadata)
        $candidateSkills = $jobSeeker->skills;
        $candidateSkillIds = $candidateSkills->pluck('id')->toArray();

        // 4. Iterasi setiap skill yang dibutuhkan dan uji multi-layer matching
        $matchedSkills = [];
        $gapSkills = [];

        foreach ($requiredSkills as $reqSkill) {
            $matchResult = $this->evaluateSkillMatch($reqSkill, $candidateSkills, $candidateSkillIds);

            if ($matchResult['is_matched']) {
                $matchedSkills[] = [
                    'id' => $reqSkill->id,
                    'title' => $reqSkill->title,
                    'title_en' => $reqSkill->title_en,
                    'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                    'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                    'match_type' => $matchResult['match_type'], // exact_id, taxonomy_hierarchy, lexical_token
                    'matched_with' => $matchResult['matched_with_title'],
                    'status_verifikasi' => 'Terverifikasi (Taksonomi ESCO)',
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

        // 5. Hitung Persentase Match Score: round( (|S_matched| / |S_required|) * 100% )
        $totalMatched = count($matchedSkills);
        $score = $totalRequired > 0 ? (int) round(($totalMatched / $totalRequired) * 100) : 100;

        // 6. Klasifikasi Skor (Decision Rules)
        $classification = $this->classifyScore($score);

        return [
            'candidate' => [
                'id' => $jobSeeker->id,
                'full_name' => $jobSeeker->full_name,
                'nik' => $jobSeeker->nik,
                'phone' => $jobSeeker->phone,
                'education_level' => $jobSeeker->educationLevel?->name,
                'desired_occupation' => $jobSeeker->desired_occupation,
                'kbji' => $jobSeeker->kbji ? [
                    'code' => $jobSeeker->kbji->code,
                    'title' => $jobSeeker->kbji->title,
                ] : null,
                'location' => ($jobSeeker->regency?->name ?? '') . ', ' . ($jobSeeker->regency?->province?->name ?? ''),
            ],
            'job' => [
                'id' => $lowongan->id,
                'judul_lowongan' => $lowongan->judul_lowongan,
                'nama_perusahaan' => $lowongan->nama_perusahaan,
                'tipe_pekerjaan' => $lowongan->tipe_pekerjaan,
                'sistem_kerja' => $lowongan->sistem_kerja,
                'kbji' => $lowongan->kbji ? [
                    'code' => $lowongan->kbji->code,
                    'title' => $lowongan->kbji->title,
                ] : null,
                'education_level' => $lowongan->educationLevel?->name,
                'location' => ($lowongan->regency?->name ?? '') . ', ' . ($lowongan->province?->name ?? ''),
                'status_lowongan' => $lowongan->status_lowongan,
            ],
            'score' => $score,
            'total_required' => $totalRequired,
            'total_matched' => $totalMatched,
            'total_gap' => count($gapSkills),
            'classification' => $classification,
            'kbji_match' => $kbjiMatch,
            'matched_skills' => $matchedSkills,
            'gap_skills' => $gapSkills,
        ];
    }

    /**
     * Mengevaluasi kesesuaian antara KBJI target pelamar dan KBJI lowongan kerja
     * Wajib berada di salah satu dari 2 level:
     * 1. Jabatan Identik (exact): Kode KBJI sama persis (misal: 2512.03 vs 2512.03)
     * 2. Sub-Golongan Identik (unit_group): 4 digit pertama sama (misal: 2512.01 vs 2512.03)
     */
    public function evaluateKbjiMatch(?KbjiClassification $candidateKbji, ?KbjiClassification $jobKbji): array
    {
        if (!$candidateKbji || !$jobKbji) {
            return [
                'is_compatible' => false,
                'match_level' => 'unspecified',
                'label' => 'KBJI Belum Ditentukan',
                'code' => null,
                'compatibility_score' => 0,
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
                'compatibility_score' => 95,
            ];
        }

        // Di luar 2 level di atas -> Tidak Kompatibel (Di-skip)
        return [
            'is_compatible' => false,
            'match_level' => 'different_group',
            'label' => 'Berbeda Sub-Golongan',
            'code' => $cCode . ' vs ' . $jCode,
            'compatibility_score' => 0,
        ];
    }

    /**
     * Evaluasi pencocokan skill dengan 3 layer:
     * 1. Exact ID
     * 2. Padanan Taksonomi (Hierarki Parent / Child)
     * 3. Leksikal / Substring / Token
     */
    protected function evaluateSkillMatch(object $reqSkill, $candidateSkills, array $candidateSkillIds): array
    {
        // Layer 1: Exact ID Match
        if (in_array($reqSkill->id, $candidateSkillIds)) {
            return [
                'is_matched' => true,
                'match_type' => 'Exact Match',
                'matched_with_title' => $reqSkill->title,
            ];
        }

        // Layer 2: Padanan Taksonomi (Hierarki Parent/Child di skill_hierarchy)
        if (!empty($candidateSkillIds)) {
            $isRelatedHierarchy = $this->checkTaxonomyHierarchy($reqSkill->id, $candidateSkillIds);
            if ($isRelatedHierarchy) {
                return [
                    'is_matched' => true,
                    'match_type' => 'Padanan Taksonomi (Hierarki)',
                    'matched_with_title' => $isRelatedHierarchy['matched_title'],
                ];
            }
        }

        // Layer 3: Leksikal / Substring / Token Match
        $reqTitle = mb_strtolower(trim($reqSkill->title));
        $reqTitleEn = mb_strtolower(trim($reqSkill->title_en ?? ''));
        $reqTokens = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $reqTitle)));

        foreach ($candidateSkills as $cSkill) {
            $candTitle = mb_strtolower(trim($cSkill->title));
            $candTitleEn = mb_strtolower(trim($cSkill->title_en ?? ''));

            // Substring check
            if (
                str_contains($candTitle, $reqTitle) ||
                str_contains($reqTitle, $candTitle) ||
                ($reqTitleEn && str_contains($candTitleEn, $reqTitleEn))
            ) {
                return [
                    'is_matched' => true,
                    'match_type' => 'Padanan Leksikal (Substring)',
                    'matched_with_title' => $cSkill->title,
                ];
            }

            // Significant Token matching (> 3 karakter)
            $candTokens = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $candTitle)));
            $commonTokens = array_intersect(
                array_filter($reqTokens, fn($t) => mb_strlen($t) >= 4),
                array_filter($candTokens, fn($t) => mb_strlen($t) >= 4)
            );

            if (!empty($commonTokens)) {
                return [
                    'is_matched' => true,
                    'match_type' => 'Padanan Token Kata Kunci',
                    'matched_with_title' => $cSkill->title,
                ];
            }
        }

        return [
            'is_matched' => false,
            'match_type' => null,
            'matched_with_title' => null,
        ];
    }

    /**
     * Memeriksa apakah ada hubungan hierarki induk-anak di tabel skill_hierarchy
     */
    protected function checkTaxonomyHierarchy(int $reqSkillId, array $candidateSkillIds): ?array
    {
        // Query apakah salah satu skill kandidat adalah parent atau child dari skill lowongan
        $row = DB::table('skill_hierarchy as sh')
            ->join('skill_nodes as sn', function ($join) use ($reqSkillId) {
                $join->on('sn.id', '=', 'sh.parent_id')
                    ->where('sh.child_id', '=', $reqSkillId)
                    ->orWhere(function ($q) use ($reqSkillId) {
                        $q->on('sn.id', '=', 'sh.child_id')
                          ->where('sh.parent_id', '=', $reqSkillId);
                    });
            })
            ->whereIn('sn.id', $candidateSkillIds)
            ->select('sn.id', 'sn.title')
            ->first();

        if ($row) {
            return [
                'matched_id' => $row->id,
                'matched_title' => $row->title,
            ];
        }

        return null;
    }

    /**
     * Rekomendasi daftar lowongan yang paling cocok untuk seorang kandidat pencari kerja
     */
    public function recommendJobsForSeeker(int $jobSeekerId, array $filters = []): array
    {
        $jobSeeker = JobSeeker::with(['kbji:id,code,title'])->find($jobSeekerId);

        if (!$jobSeeker) {
            throw new \InvalidArgumentException('Profil pencari kerja tidak ditemukan.');
        }

        $candidateSkills = $jobSeeker->skills;
        $candidateSkillIds = $candidateSkills->pluck('id')->toArray();

        // Ambil lowongan yang aktif tayang
        $query = LowonganKerja::query()
            ->with([
                // 'skills:id,title,title_en', // Temporarily disabled
                'kbji:id,code,title',
                'educationLevel:id,name',
                'province:id,name',
                'regency:id,name',
            ]);

        if (!empty($filters['status_lowongan'])) {
            $query->where('status_lowongan', $filters['status_lowongan']);
        } else {
            $query->where('status_lowongan', 'Published');
        }

        if (!empty($filters['provinsi_id'])) {
            $query->where('provinsi_id', $filters['provinsi_id']);
        }

        if (!empty($filters['tipe_pekerjaan'])) {
            $query->where('tipe_pekerjaan', $filters['tipe_pekerjaan']);
        }

        // Filter wajib pada 2 level KBJI: exact match atau 4-digit unit_group
        if ($jobSeeker->kbji) {
            $cUnit = substr(trim($jobSeeker->kbji->code), 0, 4);
            if (strlen($cUnit) === 4) {
                $query->whereHas('kbji', function ($kq) use ($cUnit) {
                    $kq->where('code', 'like', $cUnit . '%');
                });
            }
        }

        $vacancies = $query->get();
        $recommendations = [];

        foreach ($vacancies as $job) {
            $kbjiMatch = $this->evaluateKbjiMatch($jobSeeker->kbji, $job->kbji);
            if (!$kbjiMatch['is_compatible']) {
                continue;
            }

            $requiredSkills = $job->skills;
            $totalRequired = $requiredSkills->count();

            $matchedSkills = [];
            $gapSkills = [];

            foreach ($requiredSkills as $reqSkill) {
                $matchResult = $this->evaluateSkillMatch($reqSkill, $candidateSkills, $candidateSkillIds);

                if ($matchResult['is_matched']) {
                    $matchedSkills[] = [
                        'id' => $reqSkill->id,
                        'title' => $reqSkill->title,
                        'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                        'match_type' => $matchResult['match_type'],
                        'matched_with' => $matchResult['matched_with_title'],
                    ];
                } else {
                    $gapSkills[] = [
                        'id' => $reqSkill->id,
                        'title' => $reqSkill->title,
                        'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                    ];
                }
            }

            $totalMatched = count($matchedSkills);
            $score = $totalRequired > 0 ? (int) round(($totalMatched / $totalRequired) * 100) : 100;
            $classification = $this->classifyScore($score);

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
                'score' => $score,
                'total_required' => $totalRequired,
                'total_matched' => $totalMatched,
                'total_gap' => count($gapSkills),
                'classification' => $classification,
                'kbji_match' => $kbjiMatch,
                'matched_skills' => $matchedSkills,
                'gap_skills' => $gapSkills,
            ];
        }

        // Urutkan dari skor tertinggi ke terendah
        usort($recommendations, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $recommendations;
    }

    /**
     * Rekomendasi daftar kandidat pencari kerja yang paling cocok untuk sebuah lowongan
     */
    public function recommendCandidatesForJob(string $lowonganId, array $filters = []): array
    {
        $lowongan = LowonganKerja::with(['kbji:id,code,title'])->find($lowonganId);

        if (!$lowongan) {
            throw new \InvalidArgumentException('Lowongan kerja tidak ditemukan.');
        }

        $requiredSkills = $lowongan->skills;
        $totalRequired = $requiredSkills->count();

        // Ambil semua pencari kerja dengan skill & KBJI mereka
        $query = JobSeeker::query()
            ->with([
                // 'skills:id,title,title_en', // Temporarily disabled
                'educationLevel:id,name',
                'regency.province',
                'kbji:id,code,title',
            ]);

        if (!empty($filters['regency_id'])) {
            $query->where('regency_id', $filters['regency_id']);
        }

        // Filter wajib pada 2 level KBJI: exact match atau 4-digit unit_group
        if ($lowongan->kbji) {
            $jUnit = substr(trim($lowongan->kbji->code), 0, 4);
            if (strlen($jUnit) === 4) {
                $query->whereHas('kbji', function ($kq) use ($jUnit) {
                    $kq->where('code', 'like', $jUnit . '%');
                });
            }
        }

        $seekers = $query->get();
        $recommendations = [];

        foreach ($seekers as $candidate) {
            $kbjiMatch = $this->evaluateKbjiMatch($candidate->kbji, $lowongan->kbji);
            if (!$kbjiMatch['is_compatible']) {
                continue;
            }

            $candidateSkills = $candidate->skills;
            $candidateSkillIds = $candidateSkills->pluck('id')->toArray();

            $matchedSkills = [];
            $gapSkills = [];

            foreach ($requiredSkills as $reqSkill) {
                $matchResult = $this->evaluateSkillMatch($reqSkill, $candidateSkills, $candidateSkillIds);

                if ($matchResult['is_matched']) {
                    $matchedSkills[] = [
                        'id' => $reqSkill->id,
                        'title' => $reqSkill->title,
                        'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                        'match_type' => $matchResult['match_type'],
                        'matched_with' => $matchResult['matched_with_title'],
                    ];
                } else {
                    $gapSkills[] = [
                        'id' => $reqSkill->id,
                        'title' => $reqSkill->title,
                        'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                    ];
                }
            }

            $totalMatched = count($matchedSkills);
            $score = $totalRequired > 0 ? (int) round(($totalMatched / $totalRequired) * 100) : 100;
            $classification = $this->classifyScore($score);

            $recommendations[] = [
                'candidate_id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'nik' => $candidate->nik,
                'phone' => $candidate->phone,
                'gender' => $candidate->gender,
                'education_level' => $candidate->educationLevel?->name,
                'study_field_group' => $candidate->study_field_group,
                'experience_range' => $candidate->experience_range,
                'desired_occupation' => $candidate->desired_occupation,
                'kbji' => $candidate->kbji ? [
                    'id' => $candidate->kbji->id,
                    'code' => $candidate->kbji->code,
                    'title' => $candidate->kbji->title,
                ] : null,
                'location' => ($candidate->regency?->name ?? '') . ', ' . ($candidate->regency?->province?->name ?? ''),
                'score' => $score,
                'total_required' => $totalRequired,
                'total_matched' => $totalMatched,
                'total_gap' => count($gapSkills),
                'classification' => $classification,
                'kbji_match' => $kbjiMatch,
                'matched_skills' => $matchedSkills,
                'gap_skills' => $gapSkills,
            ];
        }

        // Urutkan dari skor tertinggi ke terendah
        usort($recommendations, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $recommendations;
    }

    /**
     * Rekomendasi Terpadu Pasangan (Unified Pairwise Recommendations)
     * Mengkombinasikan perspektif pelamar dan lowongan ke dalam satu dataset terpadu
     * Wajib berada di 2 level KBJI: exact match atau 4-digit unit_group
     */
    public function getUnifiedRecommendations(array $filters = []): array
    {
        // 1. Query Pencari Kerja
        $seekerQuery = JobSeeker::query()
            ->with([
                // 'skills:id,title,title_en', // Temporarily disabled
                'educationLevel:id,name',
                'regency.province',
                'kbji:id,code,title',
            ]);

        if (!empty($filters['job_seeker_id'])) {
            $seekerQuery->where('id', $filters['job_seeker_id']);
        }

        $seekers = $seekerQuery->get();

        // 2. Query Lowongan Kerja
        $jobQuery = LowonganKerja::query()
            ->with([
                // 'skills:id,title,title_en', // Temporarily disabled
                'kbji:id,code,title',
                'educationLevel:id,name',
                'province:id,name',
                'regency:id,name',
            ])
            ->where('status_lowongan', 'Published');

        if (!empty($filters['lowongan_id'])) {
            $jobQuery->where('id', $filters['lowongan_id']);
        }

        $vacancies = $jobQuery->get();

        $search = !empty($filters['search']) ? mb_strtolower(trim($filters['search'])) : null;
        $categoryFilter = !empty($filters['category']) ? $filters['category'] : null;
        $minScore = isset($filters['min_score']) && is_numeric($filters['min_score']) ? (int) $filters['min_score'] : null;

        $results = [];

        foreach ($seekers as $candidate) {
            $candidateSkills = $candidate->skills;
            $candidateSkillIds = $candidateSkills->pluck('id')->toArray();

            foreach ($vacancies as $job) {
                // Wajib di 2 level KBJI: Jabatan Identik (exact) atau Sub-Golongan Identik (unit_group 4 digit)
                $kbjiMatch = $this->evaluateKbjiMatch($candidate->kbji, $job->kbji);

                if (!$kbjiMatch['is_compatible']) {
                    continue;
                }

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

                $requiredSkills = $job->skills;
                $totalRequired = $requiredSkills->count();

                $matchedSkills = [];
                $gapSkills = [];

                foreach ($requiredSkills as $reqSkill) {
                    $matchResult = $this->evaluateSkillMatch($reqSkill, $candidateSkills, $candidateSkillIds);

                    if ($matchResult['is_matched']) {
                        $matchedSkills[] = [
                            'id' => $reqSkill->id,
                            'title' => $reqSkill->title,
                            'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                            'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                            'match_type' => $matchResult['match_type'],
                            'matched_with' => $matchResult['matched_with_title'],
                        ];
                    } else {
                        $gapSkills[] = [
                            'id' => $reqSkill->id,
                            'title' => $reqSkill->title,
                            'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                            'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                        ];
                    }
                }

                $totalMatched = count($matchedSkills);
                $score = $totalRequired > 0 ? (int) round(($totalMatched / $totalRequired) * 100) : 100;
                $classification = $this->classifyScore($score);

                // Filter kategori jika diberikan
                if ($categoryFilter) {
                    if ($categoryFilter === 'ready' || $categoryFilter === 'high') {
                        if ($score < 70) {
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
                    'total_required' => $totalRequired,
                    'total_matched' => $totalMatched,
                    'total_gap' => count($gapSkills),
                    'classification' => $classification,
                    'kbji_match' => $kbjiMatch,
                    'candidate' => [
                        'id' => $candidate->id,
                        'nik' => $candidate->nik,
                        'full_name' => $candidate->full_name,
                        'phone' => $candidate->phone,
                        'education_level' => $candidate->educationLevel?->name,
                        'desired_occupation' => $candidate->desired_occupation,
                        'kbji' => $candidate->kbji ? [
                            'id' => $candidate->kbji->id,
                            'code' => $candidate->kbji->code,
                            'title' => $candidate->kbji->title,
                        ] : null,
                        'location' => ($candidate->regency?->name ?? '') . ', ' . ($candidate->regency?->province?->name ?? ''),
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
                    'matched_skills' => $matchedSkills,
                    'gap_skills' => $gapSkills,
                ];
            }
        }

        // Urutkan dari skor tertinggi
        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Standar Aturan Keputusan (Decision Rules Sesuai Diagram Alur)
     * Skor >= 70%: Siap Ditempatkan Langsung (Rekomendasikan ke Perusahaan)
     * 40% <= Skor < 70%: Perlu Pelatihan Skill Gap (Daftarkan ke BPVP / BLK)
     * Skor < 40%: Belum Sesuai (Prioritas Bimbingan Karir Dasar)
     */
    private function classifyScore(int $score): array
    {
        if ($score === 100) {
            return [
                'category' => 'perfect',
                'label' => '100% Siap Kerja',
                'badge_color' => 'emerald',
                'action_label' => 'Rekomendasikan ke Perusahaan',
                'action_key' => 'recommend_company',
                'description' => 'Seluruh keterampilan lowongan telah dipenuhi pelamar.',
            ];
        } elseif ($score >= 70) {
            return [
                'category' => 'high',
                'label' => 'Siap Ditempatkan Langsung',
                'badge_color' => 'blue',
                'action_label' => 'Rekomendasikan ke Perusahaan',
                'action_key' => 'recommend_company',
                'description' => 'Memenuhi sebagian besar kompetensi esensial yang disyaratkan.',
            ];
        } elseif ($score >= 40) {
            return [
                'category' => 'gap',
                'label' => 'Perlu Pelatihan Skill Gap',
                'badge_color' => 'amber',
                'action_label' => 'Lihat Detail Gap',
                'action_key' => 'skill_gap_details',
                'description' => 'Memiliki modal dasar, namun terdapat kesenjangan skill yang perlu ditingkatkan.',
            ];
        } else {
            return [
                'category' => 'low',
                'label' => 'Belum Sesuai',
                'badge_color' => 'slate',
                'action_label' => 'Bimbingan Karir Dasar',
                'action_key' => 'career_guidance',
                'description' => 'Kesenjangan keterampilan masih cukup besar untuk formasi ini.',
            ];
        }
    }
}
