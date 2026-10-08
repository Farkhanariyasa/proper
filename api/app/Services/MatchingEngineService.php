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

        $jobSeeker = JobSeeker::with(['educationLevel', 'regency.province'])
            ->find($jobSeekerId);

        if (!$jobSeeker) {
            throw new \InvalidArgumentException('Profil pencari kerja tidak ditemukan.');
        }

        // 1. Evaluasi kesesuaian jabatan KBJI dan Pendidikan
        $kbjiMatch = $this->evaluateKbjiMatch($jobSeeker->kbji, $lowongan->kbji);
        $educationMatch = $this->evaluateEducationMatch($jobSeeker->educationLevel, $lowongan->educationLevel);

        // 2. Muat skill yang disyaratkan oleh lowongan (S_required)
        $requiredSkills = $lowongan->skills;
        $totalRequired = $requiredSkills->count();

        // 3. Muat skill yang dimiliki pelamar (dengan metadata)
        $candidateSkills = $jobSeeker->skills;
        if ($candidateSkills->isEmpty()) {
            app(\App\Services\CandidateSkillExtractorService::class)->syncSkillsForJobSeeker($jobSeeker);
            $jobSeeker->load(['skills' => function ($q) {
                $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
            }]);
            $candidateSkills = $jobSeeker->skills;
        }
        $candidateSkillIds = $candidateSkills->pluck('id')->toArray();
        $rawProfile = [
            'keahlian' => $jobSeeker->keahlian,
            'experience' => $jobSeeker->experience,
            'sertifikasi' => $jobSeeker->sertifikasi,
        ];

        // 4. Iterasi setiap skill yang dibutuhkan dan uji multi-layer matching
        $matchedSkills = [];
        $gapSkills = [];

        foreach ($requiredSkills as $reqSkill) {
            $matchResult = $this->evaluateSkillMatch($reqSkill, $candidateSkills, $candidateSkillIds, $rawProfile);

            if ($matchResult['is_matched']) {
                $matchedSkills[] = [
                    'id' => $reqSkill->id,
                    'title' => $reqSkill->title,
                    'title_en' => $reqSkill->title_en,
                    'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                    'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                    'match_type' => $matchResult['match_type'], // exact_id, taxonomy_hierarchy, lexical_token, profile_source
                    'matched_with' => $matchResult['matched_with_title'],
                    'source' => $matchResult['source'] ?? 'keahlian',
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

        // Jika jenjang pendidikan kandidat tidak memenuhi kualifikasi lowongan:
        if (!$educationMatch['is_matched']) {
            $classification = [
                'category' => 'disqualified',
                'label' => 'Pendidikan Tidak Memenuhi',
                'badge_color' => 'rose',
                'action_label' => 'Tidak Memenuhi Syarat',
                'action_key' => 'not_eligible_education',
                'description' => 'Jenjang pendidikan kandidat (' . ($jobSeeker->educationLevel?->name ?? 'Belum Terdata') . ') di bawah syarat minimal lowongan (' . ($lowongan->educationLevel?->name ?? 'Min. Tertentu') . ').',
            ];
        }

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
            'education_match' => $educationMatch,
            'matched_skills' => $matchedSkills,
            'gap_skills' => $gapSkills,
        ];
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
                'is_matched' => false,
                'status' => 'belum_diisi',
                'label' => 'Pendidikan Belum Terdata',
                'score' => 50,
                'required_level' => $jName,
                'candidate_level' => 'Belum Ditentukan',
            ];
        }

        if ($cLevel === $jLevel) {
            return [
                'is_matched' => true,
                'status' => 'sesuai',
                'label' => 'Sesuai Jenjang (' . $jName . ')',
                'score' => 100,
                'required_level' => $jName,
                'candidate_level' => $cName,
            ];
        }

        if ($cLevel > $jLevel) {
            return [
                'is_matched' => true,
                'status' => 'melebihi',
                'label' => 'Memenuhi (Di Atas Syarat Min. ' . $jName . ')',
                'score' => 100,
                'required_level' => $jName,
                'candidate_level' => $cName,
            ];
        }

        $gap = $jLevel - $cLevel;
        return [
            'is_matched' => false,
            'status' => 'di_bawah_syarat',
            'label' => 'Di Bawah Syarat (Butuh Min. ' . $jName . ')',
            'score' => max(0, 100 - ($gap * 30)),
            'required_level' => $jName,
            'candidate_level' => $cName,
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
                'is_compatible' => true,
                'match_level' => 'unspecified',
                'label' => 'KBJI Belum Ditentukan',
                'code' => null,
                'compatibility_score' => 70,
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
     * Evaluasi pencocokan skill dengan 4 layer:
     * 1. Exact ID
     * 2. Padanan Taksonomi (Hierarki Parent / Child)
     * 3. Leksikal / Substring / Token
     * 4. Padanan Profil Komprehensif (Keahlian, Experience, Sertifikasi)
     */
    protected function evaluateSkillMatch(object $reqSkill, $candidateSkills, array $candidateSkillIds, ?array $rawProfile = null): array
    {
        // Layer 1: Exact ID Match
        if (in_array($reqSkill->id, $candidateSkillIds)) {
            $cSkill = $candidateSkills->firstWhere('id', $reqSkill->id);
            return [
                'is_matched' => true,
                'match_type' => 'Exact Match',
                'matched_with_title' => $reqSkill->title,
                'source' => $cSkill->pivot->source ?? $cSkill->source ?? 'keahlian',
            ];
        }

        // Layer 2: Padanan Taksonomi (Hierarki Parent/Child di skill_hierarchy)
        if (!empty($candidateSkillIds)) {
            $isRelatedHierarchy = $this->checkTaxonomyHierarchy($reqSkill->id, $candidateSkillIds);
            if ($isRelatedHierarchy) {
                $cSkill = $candidateSkills->firstWhere('id', $isRelatedHierarchy['matched_id']);
                return [
                    'is_matched' => true,
                    'match_type' => 'Padanan Taksonomi (Hierarki)',
                    'matched_with_title' => $isRelatedHierarchy['matched_title'],
                    'source' => $cSkill->pivot->source ?? $cSkill->source ?? 'keahlian',
                ];
            }
        }

        // Layer 3: Leksikal / Substring / Token Match dengan Skill Kandidat
        $reqTitle = mb_strtolower(trim($reqSkill->title));
        $reqTitleEn = mb_strtolower(trim($reqSkill->title_en ?? ''));
        $reqTokens = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $reqTitle)));

        foreach ($candidateSkills as $cSkill) {
            $candTitle = mb_strtolower(trim($cSkill->title));
            $candTitleEn = mb_strtolower(trim($cSkill->title_en ?? ''));
            $src = $cSkill->pivot->source ?? $cSkill->source ?? 'keahlian';

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
                    'source' => $src,
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
                    'source' => $src,
                ];
            }
        }

        // Layer 4: Padanan Tekstual Profil Kandidat (Keahlian, Experience, Sertifikasi)
        if ($rawProfile) {
            $keahlianText = mb_strtolower($rawProfile['keahlian'] ?? '');
            if ($keahlianText !== '') {
                if (str_contains($keahlianText, $reqTitle) || ($reqTitleEn && str_contains($keahlianText, $reqTitleEn))) {
                    return [
                        'is_matched' => true,
                        'match_type' => 'Padanan Profil (Keahlian)',
                        'matched_with_title' => $reqSkill->title,
                        'source' => 'keahlian',
                    ];
                }
                $kTokens = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $keahlianText)), fn($t) => mb_strlen($t) >= 4);
                if (!empty(array_intersect(array_filter($reqTokens, fn($t) => mb_strlen($t) >= 4), $kTokens))) {
                    return [
                        'is_matched' => true,
                        'match_type' => 'Padanan Kata Kunci Keahlian',
                        'matched_with_title' => $reqSkill->title,
                        'source' => 'keahlian',
                    ];
                }
            }

            $expText = mb_strtolower($rawProfile['experience'] ?? '');
            if ($expText !== '') {
                if (str_contains($expText, $reqTitle) || ($reqTitleEn && str_contains($expText, $reqTitleEn))) {
                    return [
                        'is_matched' => true,
                        'match_type' => 'Padanan Riwayat (Pengalaman)',
                        'matched_with_title' => $reqSkill->title,
                        'source' => 'experience',
                    ];
                }
                $eTokens = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $expText)), fn($t) => mb_strlen($t) >= 4);
                if (!empty(array_intersect(array_filter($reqTokens, fn($t) => mb_strlen($t) >= 4), $eTokens))) {
                    return [
                        'is_matched' => true,
                        'match_type' => 'Padanan Kata Kunci Pengalaman',
                        'matched_with_title' => $reqSkill->title,
                        'source' => 'experience',
                    ];
                }
            }

            $sertText = mb_strtolower($rawProfile['sertifikasi'] ?? '');
            if ($sertText !== '') {
                if (str_contains($sertText, $reqTitle) || ($reqTitleEn && str_contains($sertText, $reqTitleEn))) {
                    return [
                        'is_matched' => true,
                        'match_type' => 'Padanan Dokumen (Sertifikasi)',
                        'matched_with_title' => $reqSkill->title,
                        'source' => 'sertifikasi',
                    ];
                }
                $sTokens = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $sertText)), fn($t) => mb_strlen($t) >= 4);
                if (!empty(array_intersect(array_filter($reqTokens, fn($t) => mb_strlen($t) >= 4), $sTokens))) {
                    return [
                        'is_matched' => true,
                        'match_type' => 'Padanan Kata Kunci Sertifikasi',
                        'matched_with_title' => $reqSkill->title,
                        'source' => 'sertifikasi',
                    ];
                }
            }
        }

        return [
            'is_matched' => false,
            'match_type' => null,
            'matched_with_title' => null,
            'source' => null,
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
        $jobSeeker = JobSeeker::find($jobSeekerId);

        if (!$jobSeeker) {
            throw new \InvalidArgumentException('Profil pencari kerja tidak ditemukan.');
        }

        $candidateSkills = $jobSeeker->skills;
        if ($candidateSkills->isEmpty()) {
            app(\App\Services\CandidateSkillExtractorService::class)->syncSkillsForJobSeeker($jobSeeker);
            $jobSeeker->load(['skills' => function ($q) {
                $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
            }]);
            $candidateSkills = $jobSeeker->skills;
        }
        $candidateSkillIds = $candidateSkills->pluck('id')->toArray();
        $rawProfile = [
            'keahlian' => $jobSeeker->keahlian,
            'experience' => $jobSeeker->experience,
            'sertifikasi' => $jobSeeker->sertifikasi,
        ];

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

            $educationMatch = $this->evaluateEducationMatch($jobSeeker->educationLevel, $job->educationLevel);

            // Syarat Pendidikan Mutlak: Jika tidak memenuhi kualifikasi minimal, jangan dimatch!
            if (!$educationMatch['is_matched']) {
                continue;
            }

            $requiredSkills = $job->skills;
            $totalRequired = $requiredSkills->count();

            $matchedSkills = [];
            $gapSkills = [];

            foreach ($requiredSkills as $reqSkill) {
                $matchResult = $this->evaluateSkillMatch($reqSkill, $candidateSkills, $candidateSkillIds, $rawProfile);

                if ($matchResult['is_matched']) {
                    $matchedSkills[] = [
                        'id' => $reqSkill->id,
                        'title' => $reqSkill->title,
                        'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                        'match_type' => $matchResult['match_type'],
                        'matched_with' => $matchResult['matched_with_title'],
                        'source' => $matchResult['source'] ?? 'keahlian',
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
                'education_match' => $educationMatch,
                'matched_skills' => $matchedSkills,
                'gap_skills' => $gapSkills,
            ];
        }

        // Urutkan dari skor tertinggi ke terendah, lalu yang pendidikannya cocok
        usort($recommendations, function ($a, $b) {
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            $aEdu = $a['education_match']['is_matched'] ? 1 : 0;
            $bEdu = $b['education_match']['is_matched'] ? 1 : 0;
            return $bEdu <=> $aEdu;
        });

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

        // Ambil semua pencari kerja dengan skill & relasi mereka
        $query = JobSeeker::query()
            ->with([
                'educationLevel:id,name',
                'regency.province',
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

            $educationMatch = $this->evaluateEducationMatch($candidate->educationLevel, $lowongan->educationLevel);

            // Syarat Pendidikan Mutlak: Jika kandidat di bawah syarat minimal formasi, jangan dimatch!
            if (!$educationMatch['is_matched']) {
                continue;
            }

            $candidateSkills = $candidate->skills;
            if ($candidateSkills->isEmpty()) {
                app(\App\Services\CandidateSkillExtractorService::class)->syncSkillsForJobSeeker($candidate);
                $candidate->load(['skills' => function ($q) {
                    $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
                }]);
                $candidateSkills = $candidate->skills;
            }
            $candidateSkillIds = $candidateSkills->pluck('id')->toArray();
            $rawProfile = [
                'keahlian' => $candidate->keahlian,
                'experience' => $candidate->experience,
                'sertifikasi' => $candidate->sertifikasi,
            ];

            $matchedSkills = [];
            $gapSkills = [];

            foreach ($requiredSkills as $reqSkill) {
                $matchResult = $this->evaluateSkillMatch($reqSkill, $candidateSkills, $candidateSkillIds, $rawProfile);

                if ($matchResult['is_matched']) {
                    $matchedSkills[] = [
                        'id' => $reqSkill->id,
                        'title' => $reqSkill->title,
                        'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                        'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                        'match_type' => $matchResult['match_type'],
                        'matched_with' => $matchResult['matched_with_title'],
                        'source' => $matchResult['source'] ?? 'keahlian',
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
                'education_match' => $educationMatch,
                'matched_skills' => $matchedSkills,
                'gap_skills' => $gapSkills,
            ];
        }

        // Urutkan dari skor tertinggi ke terendah, lalu yang pendidikannya cocok
        usort($recommendations, function ($a, $b) {
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            $aEdu = $a['education_match']['is_matched'] ? 1 : 0;
            $bEdu = $b['education_match']['is_matched'] ? 1 : 0;
            return $bEdu <=> $aEdu;
        });

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
                'educationLevel:id,name',
                'regency.province',
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
            if ($candidateSkills->isEmpty()) {
                app(\App\Services\CandidateSkillExtractorService::class)->syncSkillsForJobSeeker($candidate);
                $candidate->load(['skills' => function ($q) {
                    $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
                }]);
                $candidateSkills = $candidate->skills;
            }
            $candidateSkillIds = $candidateSkills->pluck('id')->toArray();
            $rawProfile = [
                'keahlian' => $candidate->keahlian,
                'experience' => $candidate->experience,
                'sertifikasi' => $candidate->sertifikasi,
            ];

            foreach ($vacancies as $job) {
                // Wajib di 2 level KBJI: Jabatan Identik (exact) atau Sub-Golongan Identik (unit_group 4 digit)
                $kbjiMatch = $this->evaluateKbjiMatch($candidate->kbji, $job->kbji);

                if (!$kbjiMatch['is_compatible']) {
                    continue;
                }

                $educationMatch = $this->evaluateEducationMatch($candidate->educationLevel, $job->educationLevel);

                // Syarat Pendidikan Mutlak: Jika tidak memenuhi, jangan dimasukkan ke rekomendasi
                if (!$educationMatch['is_matched']) {
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
                    $matchResult = $this->evaluateSkillMatch($reqSkill, $candidateSkills, $candidateSkillIds, $rawProfile);

                    if ($matchResult['is_matched']) {
                        $matchedSkills[] = [
                            'id' => $reqSkill->id,
                            'title' => $reqSkill->title,
                            'tipe_keahlian' => $reqSkill->pivot->tipe_keahlian ?? 'wajib',
                            'level_kemahiran' => $reqSkill->pivot->level_kemahiran ?? 'menengah',
                            'match_type' => $matchResult['match_type'],
                            'matched_with' => $matchResult['matched_with_title'],
                            'source' => $matchResult['source'] ?? 'keahlian',
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
                    'education_match' => $educationMatch,
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

        // Urutkan dari skor tertinggi ke terendah, lalu yang pendidikannya cocok
        usort($results, function ($a, $b) {
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            $aEdu = $a['education_match']['is_matched'] ? 1 : 0;
            $bEdu = $b['education_match']['is_matched'] ? 1 : 0;
            return $bEdu <=> $aEdu;
        });

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
