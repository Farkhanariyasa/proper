<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MatchingEngineService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchingController extends Controller
{
    protected MatchingEngineService $matchingService;

    public function __construct(MatchingEngineService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * POST /api/rekomendasi/lowongan
     * Endpoint untuk proses matching pencaker dengan lowongan berdasarkan judul pekerjaan dan skill (Top 10)
     */
    public function recommendLowongan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pencaker_id' => 'required|integer',
            'pekerjaan' => 'nullable|string',
            'judul_pekerjaan' => 'nullable|string',
            'kbji_code' => 'nullable',
            'provinsi_id' => 'nullable',
            'kabkota_id' => 'nullable',
            'skills' => 'nullable|array',
            'filter_pendidikan' => 'nullable|boolean',
        ]);

        $keyword = trim($validated['pekerjaan'] ?? $validated['judul_pekerjaan'] ?? '');

        try {
            // Ambil profil pencaker beserta riwayat pendidikan
            $seeker = \App\Models\JobSeeker::with(['skills', 'educationLevel', 'regency.province', 'province'])->find($validated['pencaker_id']);

            if (!$seeker) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Profil pencari kerja tidak ditemukan.'
                ], 404);
            }

            // 1. Query Lowongan Kerja dari tabel req_pk_loker via model LowonganKerja (Hanya yang berstatus Published)
            $query = \App\Models\LowonganKerja::published()->with(['skills', 'province', 'regency', 'educationLevel']);

            if (!empty($validated['provinsi_id'])) {
                $query->where('provinsi_id', (string) $validated['provinsi_id']);
            }
                        
            if (!empty($validated['kabkota_id'])) {
                $query->where('regency_id', (string) $validated['kabkota_id']);
            }

            // Pencarian berdasarkan kata kunci judul/deskripsi di tabel req_pk_loker
            if (!empty($keyword)) {
                $words = array_filter(explode(' ', $keyword), function($w) {
                    return mb_strlen(trim($w)) >= 2;
                });

                $query->where(function($q) use ($keyword, $words) {
                    $q->where('judul_pekerjaan', 'ILIKE', '%' . $keyword . '%')
                      ->orWhere('deskripsi_pekerjaan', 'ILIKE', '%' . $keyword . '%')
                      ->orWhere('nama_perusahaan', 'ILIKE', '%' . $keyword . '%');
                    foreach ($words as $word) {
                        $w = trim($word);
                        $q->orWhere('judul_pekerjaan', 'ILIKE', '%' . $w . '%')
                          ->orWhere('deskripsi_pekerjaan', 'ILIKE', '%' . $w . '%');
                    }
                });
            }

            $lowonganList = $query->take(80)->get();

            // Fallback: Jika tidak ada lowongan yang cocok kata kunci spesifik, ambil lowongan representatif berstatus Published
            if ($lowonganList->isEmpty()) {
                $fallbackQuery = \App\Models\LowonganKerja::published()->with(['skills', 'province', 'regency', 'educationLevel']);
                if (!empty($validated['provinsi_id'])) {
                    $fallbackQuery->where('provinsi_id', (string) $validated['provinsi_id']);
                }
                $lowonganList = $fallbackQuery->latest('id')->take(40)->get();
            }

            // 2. Skoring menggunakan Multi-Criteria Weighted Matching Engine (Bebas ID Taksonomi)
            $recommendations = [];
            $keywordLower = strtolower($keyword);

            foreach ($lowonganList as $lowongan) {
                $matchResult = $this->matchingService->computeCompositeMatch($seeker, $lowongan);

                // Filter pendidikan mutlak jika diaktifkan (dengan toleransi gap wajar)
                if (!empty($validated['filter_pendidikan']) && ($matchResult['education_match']['status'] ?? '') === 'di_bawah_syarat' && !$matchResult['education_match']['is_matched']) {
                    if (($matchResult['education_match']['score'] ?? 0) < 30) {
                        continue;
                    }
                }

                $titleLower = strtolower($lowongan->judul_lowongan ?? '');
                $isExactPhrase = (!empty($keywordLower) && str_contains($titleLower, $keywordLower)) ? 1 : 0;

                // Lampirkan hasil skill sintesis ke objek lowongan agar dapat dirender oleh modal analisis gap di frontend
                $displaySkills = collect($matchResult['matched_skills'] ?? [])->map(function($sk, $idx) {
                    return (object)[
                        'id' => is_array($sk) ? ($sk['id'] ?? (1000 + $idx)) : (1000 + $idx),
                        'title' => is_array($sk) ? ($sk['title'] ?? '') : (string)$sk,
                        'name' => is_array($sk) ? ($sk['title'] ?? '') : (string)$sk,
                    ];
                });
                if ($lowongan->skills->isEmpty() && $displaySkills->isNotEmpty()) {
                    $lowongan->setRelation('skills', $displaySkills);
                }

                $recommendations[] = [
                    'lowongan' => $lowongan,
                    'match_score' => $matchResult['score'],
                    'matched_skills_count' => $matchResult['total_matched'] ?? count($matchResult['matched_skills'] ?? []),
                    'education_match' => $matchResult['education_match'],
                    'is_exact_phrase' => $isExactPhrase,
                    'classification' => $matchResult['classification'],
                    'matched_skills' => $matchResult['matched_skills'] ?? [],
                    'gap_skills' => $matchResult['gap_skills'] ?? [],
                    'score_breakdown' => $matchResult['score_breakdown'] ?? null,
                ];
            }

            // 3. Sorting berdasar kecocokan frase kata kunci & skor komposit matching
            usort($recommendations, function ($a, $b) {
                // Utamakan yang mengandung frase kata kunci pencarian
                if ($a['is_exact_phrase'] !== $b['is_exact_phrase']) {
                    return $b['is_exact_phrase'] <=> $a['is_exact_phrase'];
                }

                // Kemudian urutkan skor matching tertinggi
                if ($a['match_score'] !== $b['match_score']) {
                    return $b['match_score'] <=> $a['match_score'];
                }

                return ($b['matched_skills_count'] ?? 0) <=> ($a['matched_skills_count'] ?? 0);
            });
            
            $topRecommendations = array_slice($recommendations, 0, 10);
            $totalPublished = \App\Models\LowonganKerja::published()->count();

            return response()->json([
                'status' => 'success',
                'data' => $topRecommendations,
                'pencaker_education' => $seeker?->educationLevel?->name ?? ($seeker?->pendidikan ?? null),
                'total_found' => count($recommendations),
                'total_published_available' => $totalPublished,
                'keyword' => $keyword
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat memproses rekomendasi lowongan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/rekomendasi/jobs-for-seeker/{jobSeekerId}
     * Rekomendasi lowongan pekerjaan yang cocok untuk kandidat tertentu
     */
    public function jobsForSeeker(Request $request, int $jobSeekerId): JsonResponse
    {
        try {
            $filters = $request->only(['status_lowongan', 'provinsi_id', 'tipe_pekerjaan']);
            $recommendations = $this->matchingService->recommendJobsForSeeker($jobSeekerId, $filters);

            return response()->json([
                'status' => 'success',
                'job_seeker_id' => $jobSeekerId,
                'total_results' => count($recommendations),
                'data' => $recommendations,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat memproses rekomendasi lowongan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/rekomendasi/candidates-for-job/{lowonganId}
     * Rekomendasi kandidat pencari kerja yang cocok untuk lowongan tertentu
     */
    public function candidatesForJob(Request $request, string $lowonganId): JsonResponse
    {
        try {
            $filters = $request->only(['regency_id']);
            $candidates = $this->matchingService->recommendCandidatesForJob($lowonganId, $filters);

            return response()->json([
                'status' => 'success',
                'lowongan_id' => $lowonganId,
                'total_results' => count($candidates),
                'data' => $candidates,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat memproses rekomendasi kandidat.',
            ], 500);
        }
    }

    /**
     * GET /api/rekomendasi/analysis?job_seeker_id=1&lowongan_id=uuid
     * Analisis pairwise mendalam kesesuaian antara 1 kandidat dan 1 lowongan
     */
    public function pairAnalysis(Request $request): JsonResponse
    {
        $request->validate([
            'job_seeker_id' => ['required', 'integer'],
            'lowongan_id' => ['required', 'string'],
        ]);

        try {
            $result = $this->matchingService->matchPair(
                (int) $request->query('job_seeker_id'),
                (string) $request->query('lowongan_id')
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menganalisis kesesuaian profil.',
            ], 500);
        }
    }

    /**
     * GET /api/rekomendasi/pairs
     * Daftar rekomendasi terpadu (Pelamar ⟷ Lowongan) dalam satu tabel matriks
     */
    public function unifiedPairs(Request $request): JsonResponse
    {
        try {
            $filters = [
                'job_seeker_id' => $request->query('job_seeker_id') ? (int) $request->query('job_seeker_id') : null,
                'lowongan_id' => $request->query('lowongan_id'),
                'category' => $request->query('category'),
                'min_score' => $request->query('min_score'),
                'search' => $request->query('search'),
            ];

            $pairs = $this->matchingService->getUnifiedRecommendations($filters);

            return response()->json([
                'status' => 'success',
                'total_results' => count($pairs),
                'data' => $pairs,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat memuat data rekomendasi terpadu: ' . $e->getMessage(),
            ], 500);
        }
    }
}
