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
