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
            $seeker = \App\Models\JobSeeker::with(['skills', 'educationLevel'])->find($validated['pencaker_id']);

            // 1. Filter Lowongan Awal
            $query = \App\Models\ReqPkLoker::with(['skills', 'province', 'regency', 'kbji', 'educationLevel'])
                        ->whereIn('status_loker', ['tayang', 'published']);

            // Kualifikasi Pendidikan Wajib Memenuhi:
            // Jangan ambil lowongan yang mensyaratkan jenjang pendidikan di atas jenjang pendidikan pencaker
            if ($seeker?->educationLevel) {
                $cLevel = $seeker->educationLevel->sort_order ?? $seeker->educationLevel->id;
                $query->where(function($q) use ($cLevel) {
                    $q->whereNull('education_level_id')
                      ->orWhereHas('educationLevel', function($eq) use ($cLevel) {
                          $eq->where('sort_order', '<=', $cLevel);
                      });
                });
            }

            // Pencarian berdasarkan kata kunci judul pekerjaan
            if (!empty($keyword)) {
                $words = array_filter(explode(' ', $keyword), function($w) {
                    return mb_strlen(trim($w)) >= 2;
                });

                $query->where(function($q) use ($keyword, $words) {
                    $q->where('judul_pekerjaan', 'ILIKE', '%' . $keyword . '%');
                    foreach ($words as $word) {
                        $q->orWhere('judul_pekerjaan', 'ILIKE', '%' . trim($word) . '%');
                    }
                });
            } elseif (!empty($validated['kbji_code'])) {
                // Fallback untuk backward-compatibility jika kbji_code dikirim
                $baseKbji = substr((string)$validated['kbji_code'], 0, 4);
                $query->whereHas('kbji', function($q) use ($baseKbji) {
                    $q->where('code', 'LIKE', $baseKbji . '%');
                });
            }

            if (!empty($validated['provinsi_id'])) {
                $query->where('provinsi_id', (string) $validated['provinsi_id']);
            }
                        
            if (!empty($validated['kabkota_id'])) {
                $query->where('regency_id', (string) $validated['kabkota_id']);
            }

            $lowonganList = $query->get();

            // Ambil skill pencaker (dari payload atau relasi pencaker)
            $pencakerSkills = $validated['skills'] ?? [];
            if (empty($pencakerSkills) && $seeker) {
                $pencakerSkills = $seeker->skills->pluck('id')->toArray();
            }

            // 2. Skoring / Matching
            $recommendations = [];
            $keywordLower = strtolower($keyword);

            foreach ($lowonganList as $lowongan) {
                // Evaluasi kecocokan jenjang pendidikan kandidat vs syarat lowongan
                $eduMatch = $this->matchingService->evaluateEducationMatch(
                    $seeker?->educationLevel,
                    $lowongan->educationLevel
                );

                // SYARAT PENDIDIKAN MUTLAK: Jika pendidikan tidak memenuhi syarat minimal, jangan dimatch!
                if (!$eduMatch['is_matched']) {
                    continue;
                }

                $lowonganSkills = $lowongan->skills->pluck('id')->toArray();
                
                // Hitung irisan skill yang cocok
                $matchedSkills = array_intersect($pencakerSkills, $lowonganSkills);
                $matchCount = count($matchedSkills);
                $totalLowonganSkills = count($lowonganSkills);
                if ($totalLowonganSkills > 0) {
                    $score = round(($matchCount / $totalLowonganSkills) * 100);
                } else {
                    $score = 0; 
                }

                $titleLower = strtolower($lowongan->judul_pekerjaan ?? '');
                $isExactPhrase = (!empty($keywordLower) && str_contains($titleLower, $keywordLower)) ? 1 : 0;

                $recommendations[] = [
                    'lowongan' => $lowongan,
                    'match_score' => $score,
                    'matched_skills_count' => $matchCount,
                    'education_match' => $eduMatch,
                    'is_exact_phrase' => $isExactPhrase,
                ];
            }

            // 3. Sorting (Descending berdasar match_score, kesesuaian pendidikan, matched_skills_count, kesesuaian judul) & Limit (Top 10)
            usort($recommendations, function ($a, $b) {
                // Primary: skill match score (descending)
                if ($a['match_score'] !== $b['match_score']) {
                    return $b['match_score'] <=> $a['match_score'];
                }

                // Secondary: kesesuaian kualifikasi pendidikan (yang memenuhi didahulukan jika skor skill setara)
                $aEdu = $a['education_match']['is_matched'] ? 1 : 0;
                $bEdu = $b['education_match']['is_matched'] ? 1 : 0;
                if ($aEdu !== $bEdu) {
                    return $bEdu <=> $aEdu;
                }
                
                // Tertiary: jumlah skill yang cocok (descending)
                if ($a['matched_skills_count'] !== $b['matched_skills_count']) {
                    return $b['matched_skills_count'] <=> $a['matched_skills_count'];
                }

                // Quaternary: kesesuaian frase judul pekerjaan
                return $b['is_exact_phrase'] <=> $a['is_exact_phrase'];
            });
            
            $topRecommendations = array_slice($recommendations, 0, 10);
            $totalPublished = \App\Models\ReqPkLoker::whereIn('status_loker', ['tayang', 'published'])->count();

            return response()->json([
                'status' => 'success',
                'data' => $topRecommendations,
                'pencaker_education' => $seeker?->educationLevel?->name ?? ($seeker?->pendidikan ?? null),
                'total_found' => count($lowonganList),
                'total_published_available' => $totalPublished,
                'keyword' => $keyword
            ]);
            
        } catch (Exception $e) {
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
