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
        // Hubungan dengan master data sekarang menggunakan relasi standar dengan foreign key id
        // Kita meload relasi master dan 'skills' count
        $query = JobSeeker::query()
            ->with(['province', 'regency', 'educationLevel'])
            ->withCount('skills');

        // Filter pencarian nama
        if ($request->filled('q')) {
            $keyword = trim((string) $request->query('q'));
            $query->where('name', 'ILIKE', "%{$keyword}%");
        }

        // Filter provinsi (string text bebas dari DB)
        if ($request->filled('provinsi')) {
            $query->where('provinsi', 'ILIKE', '%' . $request->query('provinsi') . '%');
        }

        // Filter kabupaten/kota (string text bebas dari DB)
        if ($request->filled('kab_kota')) {
            $query->where('kab_kota', 'ILIKE', '%' . $request->query('kab_kota') . '%');
        }

        // Filter tingkat pendidikan (string dari DB)
        if ($request->filled('pendidikan')) {
            $query->where('pendidikan', 'ILIKE', '%' . $request->query('pendidikan') . '%');
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
            ],
        ]);
    }

    /**
     * GET /api/job-seekers/{id}/skills
     * Ambil skill pencari kerja. Jika kosong, ekstrak dari field 'keahlian' (MVP text matching).
     */
    public function getSkills($id): JsonResponse
    {
        $jobSeeker = JobSeeker::with('skills:id,code,title,title_en,type,description')->find($id);

        if (!$jobSeeker) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        // Jika skill masih kosong, lakukan auto-extract sederhana dari teks (MVP)
        if ($jobSeeker->skills->isEmpty() && !empty($jobSeeker->keahlian)) {
            // Simulasi auto extract skill dari teks keahlian
            $keywords = array_filter(array_map('trim', explode(',', $jobSeeker->keahlian)));
            
            if (count($keywords) > 0) {
                $matchedSkillIds = \App\Models\SkillNode::where(function ($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->orWhere('title', 'ILIKE', '%' . $keyword . '%')
                              ->orWhere('title_en', 'ILIKE', '%' . $keyword . '%');
                    }
                })->limit(5)->pluck('id')->toArray();

                if (!empty($matchedSkillIds)) {
                    // Simpan ke pivot table pencaker_esco_skills
                    $syncData = [];
                    foreach ($matchedSkillIds as $skillId) {
                        $syncData[$skillId] = ['is_manual' => false];
                    }
                    $jobSeeker->skills()->sync($syncData);
                    
                    // Reload skill setelah di-save
                    $jobSeeker->load('skills:id,code,title,title_en,type,description');
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $jobSeeker->skills,
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

        // Sync dengan status is_manual = true karena ini di-update operator
        $syncData = [];
        foreach ($request->skills as $skillId) {
            $syncData[$skillId] = ['is_manual' => true];
        }
        
        $jobSeeker->skills()->sync($syncData);
        $jobSeeker->load('skills:id,code,title,title_en,type,description');

        return response()->json([
            'status' => 'success',
            'message' => 'Keahlian pencari kerja berhasil diperbarui',
            'data' => $jobSeeker->skills
        ]);
    }
}
