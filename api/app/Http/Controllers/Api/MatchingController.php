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
     * Endpoint untuk proses matching pencaker dengan lowongan berdasarkan filter spesifik (Fase 2 & 3)
     */
    public function recommendLowongan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pencaker_id' => 'required|integer',
            'kbji_code' => 'required|string',
            'provinsi_id' => 'required|string',
            'kabkota_id' => 'nullable|string',
            'skills' => 'required|array',
        ]);

        try {
            // Karena logikanya spesifik untuk fitur ini, kita bisa letakkan di service
            // atau langsung memfilter di controller untuk diteruskan ke proses skoring
            
            // 1. Filter Lowongan Awal
            $query = \App\Models\ReqPkLoker::with(['skills', 'province', 'regency', 'kbji'])
                        ->where('status_loker', 'tayang'); // atau sesuaikan dengan status buka
            
            // Filter by broader KBJI (4-digit group) to avoid being too strict
            $baseKbji = substr($validated['kbji_code'], 0, 4);
            $query->whereHas('kbji', function($q) use ($baseKbji) {
                $q->where('code', 'LIKE', $baseKbji . '%');
            });

            if (!empty($validated['provinsi_id'])) {
                $query->where('provinsi_id', $validated['provinsi_id']);
            }
                        
            if (!empty($validated['kabkota_id'])) {
                $query->where('regency_id', $validated['kabkota_id']);
            }

            $lowonganList = $query->get();
            $pencakerSkills = $validated['skills']; // Array ESCO Skill ID dari Pencaker

            // 2. Skoring / Matching
            $recommendations = [];
            foreach ($lowonganList as $lowongan) {
                $lowonganSkills = $lowongan->skills->pluck('id')->toArray();
                
                // Hitung irisan skill yang cocok
                $matchedSkills = array_intersect($pencakerSkills, $lowonganSkills);
                $matchCount = count($matchedSkills);
                $totalLowonganSkills = count($lowonganSkills);
                if ($totalLowonganSkills > 0) {
                    $score = round(($matchCount / $totalLowonganSkills) * 100);
                } else {
                    // Jika lowongan tidak mensyaratkan skill spesifik
                    $score = 0; 
                }

                $recommendations[] = [
                    'lowongan' => $lowongan,
                    'match_score' => $score,
                    'matched_skills_count' => $matchCount,
                ];
            }

            // 3. Sorting (Descending berdasar match_score) & Limit (Top 10)
            usort($recommendations, function ($a, $b) use ($validated) {
                // Primary: skill match score (descending)
                if ($a['match_score'] !== $b['match_score']) {
                    return $b['match_score'] <=> $a['match_score'];
                }
                
                // Secondary: KBJI closeness
                $codeA = $a['lowongan']->kbji->code ?? '';
                $codeB = $b['lowongan']->kbji->code ?? '';
                $reqCode = $validated['kbji_code'];
                
                $scoreA = ($codeA === $reqCode) ? 2 : (str_starts_with($codeA, $reqCode) ? 1 : 0);
                $scoreB = ($codeB === $reqCode) ? 2 : (str_starts_with($codeB, $reqCode) ? 1 : 0);
                
                return $scoreB <=> $scoreA;
            });
            
            $topRecommendations = array_slice($recommendations, 0, 10);

            return response()->json([
                'status' => 'success',
                'data' => $topRecommendations
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
