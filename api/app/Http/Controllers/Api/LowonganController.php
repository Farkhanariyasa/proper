<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLowonganRequest;
use App\Http\Requests\UpdateLowonganRequest;
use App\Http\Resources\LowonganResource;
use App\Models\LowonganKerja;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LowonganController extends Controller
{
    /**
     * GET /api/lowongan
     * Daftar lowongan dengan pagination, search, dan multi-filter
     */
    public function index(Request $request): JsonResponse
    {
        $query = LowonganKerja::query()
            ->with([
                'kbji:id,code,title,level',
                'educationLevel:id,name',
                'province:id,name',
                'regency:id,name',
                'skills:id,title,title_en',
            ]);

        // Filter scope dari model
        $query->filter($request->only([
            'search',
            'provinsi_id',
            'regency_id',
            'tipe_pekerjaan',
            'sistem_kerja',
            'status_lowongan',
            'education_level_id',
            'kbji_id',
        ]));

        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');
        $allowedSorts = ['created_at', 'tanggal_tutup', 'judul_lowongan', 'gaji_minimal'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
        $vacancies = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => LowonganResource::collection($vacancies),
            'meta' => [
                'current_page' => $vacancies->currentPage(),
                'last_page' => $vacancies->lastPage(),
                'per_page' => $vacancies->perPage(),
                'total' => $vacancies->total(),
            ],
        ]);
    }

    /**
     * POST /api/lowongan
     * Simpan lowongan baru beserta pivot skills standar ESCO
     */
    public function store(StoreLowonganRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $skillsData = $validated['skills'];
        unset($validated['skills']);

        // Default creator
        if (!isset($validated['created_by']) && $request->user()) {
            $validated['created_by'] = $request->user()->id;
        }

        // Generate unik slug
        $baseSlug = Str::slug($validated['judul_lowongan'] . '-' . $validated['nama_perusahaan']);
        $validated['slug'] = $baseSlug . '-' . Str::lower(Str::random(5));

        try {
            $lowongan = DB::transaction(function () use ($validated, $skillsData) {
                $item = LowonganKerja::create($validated);

                // Format sync array dengan pivot attributes
                $syncPayload = [];
                foreach ($skillsData as $sk) {
                    $syncPayload[$sk['esco_skill_id']] = [
                        'tipe_keahlian' => $sk['tipe_keahlian'] ?? 'wajib',
                        'level_kemahiran' => $sk['level_kemahiran'] ?? 'menengah',
                    ];
                }

                $item->skills()->sync($syncPayload);

                return $item;
            });

            $lowongan->load([
                'kbji:id,code,title,level',
                'educationLevel:id,name',
                'province:id,name',
                'regency:id,name',
                'skills:id,title,title_en',
                'creator:id,name,email',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Lowongan kerja berhasil ditambahkan.',
                'data' => new LowonganResource($lowongan),
            ], 201);
        } catch (Exception $e) {
            Log::error('Gagal menambahkan lowongan: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menyimpan lowongan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/lowongan/{idOrSlug}
     * Ambil detail lengkap suatu lowongan
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $lowongan = LowonganKerja::with([
            'kbji:id,code,title,level',
            'educationLevel:id,name',
            'province:id,name',
            'regency:id,name',
            'skills:id,title,title_en',
            'creator:id,name,email',
        ])
        ->where(function ($q) use ($idOrSlug) {
            $q->where('id', $idOrSlug)
              ->orWhere('slug', $idOrSlug);
        })
        ->first();

        if (!$lowongan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lowongan kerja tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new LowonganResource($lowongan),
        ]);
    }

    /**
     * PUT /api/lowongan/{id}
     * Perbarui data lowongan dan sync skills
     */
    public function update(UpdateLowonganRequest $request, string $id): JsonResponse
    {
        $lowongan = LowonganKerja::find($id);

        if (!$lowongan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lowongan kerja tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validated();
        $hasSkills = array_key_exists('skills', $validated);
        $skillsData = $hasSkills ? $validated['skills'] : null;
        unset($validated['skills']);

        try {
            DB::transaction(function () use ($lowongan, $validated, $hasSkills, $skillsData) {
                $lowongan->update($validated);

                if ($hasSkills && is_array($skillsData)) {
                    $syncPayload = [];
                    foreach ($skillsData as $sk) {
                        $syncPayload[$sk['esco_skill_id']] = [
                            'tipe_keahlian' => $sk['tipe_keahlian'] ?? 'wajib',
                            'level_kemahiran' => $sk['level_kemahiran'] ?? 'menengah',
                        ];
                    }
                    $lowongan->skills()->sync($syncPayload);
                }
            });

            $lowongan->load([
                'kbji:id,code,title,level',
                'educationLevel:id,name',
                'province:id,name',
                'regency:id,name',
                'skills:id,title,title_en',
                'creator:id,name,email',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data lowongan kerja berhasil diperbarui.',
                'data' => new LowonganResource($lowongan),
            ]);
        } catch (Exception $e) {
            Log::error('Gagal memperbarui lowongan: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat memperbarui lowongan.',
            ], 500);
        }
    }

    /**
     * DELETE /api/lowongan/{id}
     * Hapus lowongan (Soft Delete)
     */
    public function destroy(string $id): JsonResponse
    {
        $lowongan = LowonganKerja::find($id);

        if (!$lowongan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lowongan kerja tidak ditemukan.',
            ], 404);
        }

        $lowongan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Lowongan kerja berhasil dihapus.',
        ]);
    }

    /**
     * PATCH /api/lowongan/{id}/status
     * Ganti status lowongan (Draft / Published / Closed / Archived)
     */
    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status_lowongan' => ['required', 'string', 'in:Draft,Published,Closed,Archived'],
        ]);

        $lowongan = LowonganKerja::find($id);

        if (!$lowongan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lowongan kerja tidak ditemukan.',
            ], 404);
        }

        $lowongan->status_lowongan = $request->status_lowongan;
        if ($request->status_lowongan === 'Published' && !$lowongan->tanggal_buka) {
            $lowongan->tanggal_buka = now();
        }
        $lowongan->save();

        return response()->json([
            'status' => 'success',
            'message' => "Status lowongan berhasil diubah menjadi {$lowongan->status_lowongan}.",
            'data' => [
                'id' => $lowongan->id,
                'status_lowongan' => $lowongan->status_lowongan,
            ],
        ]);
    }

    /**
     * GET /api/lowongan/options
     * Pilihan standar untuk form dropdown
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'tipe_pekerjaan' => ['Full-Time', 'Part-Time', 'Kontrak', 'Magang', 'Freelance'],
                'sistem_kerja' => ['WFO', 'WFH', 'Hybrid'],
                'jenis_kelamin' => ['Semua', 'Laki-laki', 'Perempuan'],
                'status_lowongan' => ['Draft', 'Published', 'Closed', 'Archived'],
                'tipe_keahlian' => ['wajib', 'tambahan'],
                'level_kemahiran' => ['pemula', 'menengah', 'ahli'],
            ],
        ]);
    }
}
