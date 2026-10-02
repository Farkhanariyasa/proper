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
        $query = JobSeeker::query()
            ->with(['regency.province', 'educationLevel'])
            ->withCount('skills');

        // Filter pencarian nama / NIK
        if ($request->filled('q')) {
            $keyword = trim((string) $request->query('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('full_name', 'ILIKE', "%{$keyword}%")
                  ->orWhere('nik', 'LIKE', "%{$keyword}%");
            });
        }

        // Filter provinsi
        if ($request->filled('province_id')) {
            $query->whereHas('regency', function ($q) use ($request) {
                $q->where('province_id', $request->query('province_id'));
            });
        }

        // Filter kabupaten/kota
        if ($request->filled('regency_id')) {
            $query->where('regency_id', $request->query('regency_id'));
        }

        // Filter rumpun bidang pendidikan
        if ($request->filled('study_field_group')) {
            $query->where('study_field_group', $request->query('study_field_group'));
        }

        // Filter rentang pengalaman
        if ($request->filled('experience_range')) {
            $query->where('experience_range', $request->query('experience_range'));
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
        $jobSeekers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $jobSeekers->items(),
            'meta' => [
                'current_page' => $jobSeekers->currentPage(),
                'last_page' => $jobSeekers->lastPage(),
                'per_page' => $jobSeekers->perPage(),
                'total' => $jobSeekers->total(),
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
            'regency.province',
            'educationLevel',
            'skills:id,code,title,title_en,type',
            'creator:id,name,email',
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
            ],
        ]);
    }
}
