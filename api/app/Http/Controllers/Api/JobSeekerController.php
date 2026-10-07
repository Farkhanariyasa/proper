<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobSeekerRequest;
use App\Models\JobSeeker;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobSeekerController extends Controller
{
    /**
     * GET /api/job-seekers
     * Daftar pencari kerja dengan pagination & pencarian
     */
    public function index(Request $request): JsonResponse
    {
        $isLite = $request->boolean('lite');

        if ($isLite) {
            // Mode ringan untuk dropdown/autocomplete: hanya ambil kolom esensial tanpa eager-load & withCount
            $query = JobSeeker::query()->select(['id', 'profile_id', 'name']);
        } else {
            // Mode lengkap untuk tampilan tabel manajemen data pencaker
            $query = JobSeeker::query()
                ->select([
                    'id',
                    'profile_id',
                    'name',
                    'provinsi',
                    'province_id',
                    'kab_kota',
                    'regency_id',
                    'pendidikan',
                    'education_level_id',
                    'nama_sekolah',
                    'jurusan',
                    'keahlian',
                    'experience',
                    'jenis_kelamin',
                    'umur',
                ])
                ->with([
                    'province:id,name',
                    'regency:id,name,province_id',
                    'educationLevel:id,name',
                ])
                ->withCount('skills');
        }

        // Pencarian nama atau ID profil dengan pemanfaatan GIN Trigram index
        if ($request->filled('q')) {
            $keyword = trim((string) $request->query('q'));
            if (str_starts_with(strtoupper($keyword), 'PROFILE-')) {
                $query->where('profile_id', 'ILIKE', "{$keyword}%");
            } else {
                // Memanfaatkan GIN index 'idx_pencaker_name_trgm' untuk pencarian nama instan
                $query->where('name', 'ILIKE', "%{$keyword}%");
            }
        }

        // Filter provinsi_id dan regency_id (relasi)
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->query('province_id'));
        }

        if ($request->filled('regency_id')) {
            $query->where('regency_id', $request->query('regency_id'));
        }

        // Filter provinsi (string text bebas dari DB) - fallback
        if ($request->filled('provinsi')) {
            $query->where('provinsi', 'ILIKE', '%' . $request->query('provinsi') . '%');
        }

        // Filter kabupaten/kota (string text bebas dari DB) - fallback
        if ($request->filled('kab_kota')) {
            $query->where('kab_kota', 'ILIKE', '%' . $request->query('kab_kota') . '%');
        }

        // Filter jenjang pendidikan (nilai persis seperti di kolom pendidikan)
        if ($request->filled('pendidikan')) {
            $query->where('pendidikan', $request->query('pendidikan'));
        }

        // Filter rentang pengalaman
        if ($request->filled('experience')) {
            $query->where('experience', 'ILIKE', '%' . $request->query('experience') . '%');
        }

        // Assuming there is no created_at, we just order by ID or don't order explicitly
        // If there is no created_at, ordering by id desc is safer
        $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
        $jobSeekers = $query->orderBy('id', 'desc')->simplePaginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $jobSeekers->items(),
            'meta' => [
                'current_page' => $jobSeekers->currentPage(),
                'per_page' => $jobSeekers->perPage(),
                'has_more_pages' => $jobSeekers->hasMorePages(),
            ],
        ]);
    }

    /**
     * POST /api/job-seekers
     * Simpan data pencari kerja & keahlian dengan Database Transaction
     */
    public function store(StoreJobSeekerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $skillIds = $validated['skills'];
        unset($validated['skills']);

        // Default created_by to current authenticated user if available
        if (!isset($validated['created_by']) && $request->user()) {
            $validated['created_by'] = $request->user()->id;
        }

        try {
            $jobSeeker = DB::transaction(function () use ($validated, $skillIds) {
                // 1. Insert data profil pencari kerja ke job_seekers
                $seeker = JobSeeker::create($validated);

                // 2. Bulk insert ID skill ke pivot job_seeker_skills
                $seeker->skills()->sync($skillIds);

                return $seeker;
            });

            // Load relasi lengkap untuk response
            $jobSeeker->load([
                'regency.province',
                'educationLevel',
                'kbji:id,code,title',
                'skills:id,code,title,title_en,type',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profil pencari kerja berhasil didaftarkan.',
                'data' => $jobSeeker,
            ], 201);
        } catch (Exception $e) {
            Log::error('Gagal menyimpan profil pencari kerja: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menyimpan profil pencari kerja.',
            ], 500);
        }
    }

    /**
     * GET /api/job-seekers/{id}
     * Ambil detail profil pencari kerja
     */
    public function show($id): JsonResponse
    {
        $jobSeeker = JobSeeker::with([
            'province',
            'regency',
            'educationLevel',
            'skills:id,code,title,title_en,type',
        ])->find($id);

        if (!$jobSeeker) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil pencari kerja tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $jobSeeker,
        ]);
    }

    /**
     * GET /api/job-seekers/options
     * Opsi dropdown referensi standar (rumpun pendidikan & rentang pengalaman)
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'study_field_groups' => JobSeeker::STUDY_FIELD_GROUPS,
                'experience_ranges' => JobSeeker::EXPERIENCE_RANGES,
                'pendidikan' => JobSeeker::PENDIDIKAN_OPTIONS,
            ],
        ]);
    }

    /**
     * GET /api/job-seekers/{id}/skills
     * Ambil skill pencari kerja. Jika kosong (atau refresh diminta),
     * ekstrak secara otomatis berdasarkan keahlian, experience, dan sertifikasi.
     */
    public function getSkills(Request $request, $id): JsonResponse
    {
        $jobSeeker = JobSeeker::with(['skills' => function ($q) {
            $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
        }])->find($id);

        if (!$jobSeeker) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $extractor = app(\App\Services\CandidateSkillExtractorService::class);
        $forceRefresh = $request->boolean('refresh') || $request->boolean('reextract');

        // Jika skill masih kosong atau ada instruksi re-extract
        if ($jobSeeker->skills->isEmpty() || $forceRefresh) {
            $extractor->syncSkillsForJobSeeker($jobSeeker, $forceRefresh);
            $jobSeeker->load(['skills' => function ($q) {
                $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
            }]);
        }

        $allKeywords = $extractor->extractAllKeywords($jobSeeker);

        return response()->json([
            'status' => 'success',
            'data' => $jobSeeker->skills,
            'meta' => [
                'keywords' => $allKeywords,
                'source_summary' => [
                    'keahlian' => $jobSeeker->keahlian,
                    'experience' => $jobSeeker->experience,
                    'sertifikasi' => $jobSeeker->sertifikasi,
                ],
            ],
        ]);
    }

    /**
     * POST /api/job-seekers/{id}/extract-skills
     * Ekstraksi ulang skill dari keahlian, experience, dan sertifikasi secara paksa
     */
    public function extractSkills(Request $request, $id): JsonResponse
    {
        $jobSeeker = JobSeeker::find($id);

        if (!$jobSeeker) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $extractor = app(\App\Services\CandidateSkillExtractorService::class);
        $extractor->syncSkillsForJobSeeker($jobSeeker, true);
        $jobSeeker->load(['skills' => function ($q) {
            $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
        }]);

        return response()->json([
            'status' => 'success',
            'message' => 'Skill berhasil diekstraksi dari keahlian, pengalaman, dan sertifikasi.',
            'data' => $jobSeeker->skills,
            'meta' => [
                'keywords' => $extractor->extractAllKeywords($jobSeeker),
            ],
        ]);
    }

    /**
     * POST /api/job-seekers/{id}/skills
     * Update/Overwrite skill pencaker oleh operator
     */
    public function updateSkills(Request $request, $id): JsonResponse
    {
        $request->validate([
            'skills' => 'required|array',
            'skills.*' => 'integer|exists:skill_nodes,id'
        ]);

        $jobSeeker = JobSeeker::find($id);

        if (!$jobSeeker) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        // Simpan dengan status is_manual = true dan source = manual
        $syncData = [];
        foreach ($request->skills as $skillId) {
            $syncData[$skillId] = [
                'is_manual' => true,
                'source' => 'manual',
            ];
        }
        
        $jobSeeker->skills()->sync($syncData);
        $jobSeeker->load(['skills' => function ($q) {
            $q->select('skill_nodes.id', 'skill_nodes.code', 'skill_nodes.title', 'skill_nodes.title_en', 'skill_nodes.type', 'skill_nodes.description');
        }]);

        return response()->json([
            'status' => 'success',
            'message' => 'Keahlian pencari kerja berhasil diperbarui',
            'data' => $jobSeeker->skills
        ]);
    }
}
