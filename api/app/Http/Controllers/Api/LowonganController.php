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
            ->select([
                'id',
                'job_id',
                'vac_id',
                'judul_pekerjaan',
                'nama_perusahaan',
                'deskripsi_pekerjaan',
                'tipe_pekerjaan',
                'status_loker',
                'kuota',
                'rentang_gaji',
                'reg',
                'provinsi_id',
                'regency_id',
                'kbji_2026_id',
                'education_level_id',
                'tanggal_tayang',
                'tanggal_expired_lowongan',
                'tanggal_dibuat',
                'tanggal_update',
            ])
            ->with([
                'kbji:id,code,title,level',
                'province:id,name',
                'regency:id,name',
                'educationLevel:id,name',
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

        $sortBy = $request->query('sort_by', 'tanggal_tayang');
        $sortOrder = $request->query('sort_order', 'desc');
        $allowedSorts = ['tanggal_tayang', 'tanggal_expired_lowongan', 'judul_pekerjaan'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('tanggal_tayang', 'desc');
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
            $mappedData = [
                'job_id' => 'JOB-' . Str::random(4) . '-' . Str::random(4),
                'vac_id' => 'VAC-' . Str::random(4) . '-' . Str::random(4),
                'judul_pekerjaan' => $validated['judul_lowongan'] ?? null,
                'nama_perusahaan' => $validated['nama_perusahaan'] ?? null,
                'deskripsi_pekerjaan' => $validated['deskripsi_pekerjaan'] ?? null,
                'tipe_pekerjaan' => $validated['tipe_pekerjaan'] ?? null,
                'status_loker' => isset($validated['status_lowongan']) ? strtolower($validated['status_lowongan']) : 'draft',
                'kuota' => $validated['jumlah_kebutuhan'] ?? null,
                'rentang_gaji' => (isset($validated['gaji_minimal']) || isset($validated['gaji_maksimal'])) 
                    ? (($validated['gaji_minimal'] ?? '0') . '-' . ($validated['gaji_maksimal'] ?? '0')) 
                    : null,
                'reg' => $validated['alamat_lengkap_penempatan'] ?? null,
                'provinsi_id' => $validated['provinsi_id'] ?? null,
                'regency_id' => $validated['regency_id'] ?? null,
                'kbji_2026_id' => $validated['kbji_id'] ?? null,
                'education_level_id' => $validated['education_level_id'] ?? null,
                'tanggal_dibuat' => now(),
            ];

            $lowongan = DB::transaction(function () use ($mappedData, $skillsData) {
                $item = LowonganKerja::create($mappedData);

                if (!empty($skillsData)) {
                    $insertSkills = [];
                    foreach ($skillsData as $sk) {
                        $insertSkills[] = [
                            'vac_id' => $item->vac_id,
                            'esco_skill_id' => $sk['esco_skill_id'],
                            'tipe_keahlian' => $sk['tipe_keahlian'] ?? 'wajib',
                            'skor' => 1.0,
                            'metode' => 'input_manual',
                        ];
                    }
                    DB::table('lowongan_skills')->insert($insertSkills);
                }

                return $item;
            });

            $lowongan->load([
                'kbji:id,code,title,level',
                'province:id,name',
                'regency:id,name',
                'educationLevel:id,name',
                'skills:id,title,title_en',
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
            'province:id,name',
            'regency:id,name',
            'educationLevel:id,name',
            'skills:id,title,title_en',
        ])
        ->where('id', $idOrSlug)
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
            $mappedData = [];
            if (array_key_exists('judul_lowongan', $validated)) $mappedData['judul_pekerjaan'] = $validated['judul_lowongan'];
            if (array_key_exists('nama_perusahaan', $validated)) $mappedData['nama_perusahaan'] = $validated['nama_perusahaan'];
            if (array_key_exists('deskripsi_pekerjaan', $validated)) $mappedData['deskripsi_pekerjaan'] = $validated['deskripsi_pekerjaan'];
            if (array_key_exists('tipe_pekerjaan', $validated)) $mappedData['tipe_pekerjaan'] = $validated['tipe_pekerjaan'];
            if (array_key_exists('status_lowongan', $validated)) $mappedData['status_loker'] = strtolower($validated['status_lowongan']);
            if (array_key_exists('jumlah_kebutuhan', $validated)) $mappedData['kuota'] = $validated['jumlah_kebutuhan'];
            if (array_key_exists('alamat_lengkap_penempatan', $validated)) $mappedData['reg'] = $validated['alamat_lengkap_penempatan'];
            if (array_key_exists('provinsi_id', $validated)) $mappedData['provinsi_id'] = $validated['provinsi_id'];
            if (array_key_exists('regency_id', $validated)) $mappedData['regency_id'] = $validated['regency_id'];
            if (array_key_exists('kbji_id', $validated)) $mappedData['kbji_2026_id'] = $validated['kbji_id'];
            if (array_key_exists('education_level_id', $validated)) $mappedData['education_level_id'] = $validated['education_level_id'];

            if (array_key_exists('gaji_minimal', $validated) || array_key_exists('gaji_maksimal', $validated)) {
                $mappedData['rentang_gaji'] = ($validated['gaji_minimal'] ?? '0') . '-' . ($validated['gaji_maksimal'] ?? '0');
            }
            
            $mappedData['tanggal_update'] = now();

            DB::transaction(function () use ($lowongan, $mappedData) {
                $lowongan->update($mappedData);
            });

            $lowongan->load([
                'kbji:id,code,title,level',
                'province:id,name',
                'regency:id,name',
                'educationLevel:id,name',
                'skills:id,title,title_en',
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

        DB::transaction(function () use ($lowongan) {
            if (!empty($lowongan->vac_id)) {
                DB::table('lowongan_skills')->where('vac_id', $lowongan->vac_id)->delete();
            }
            $lowongan->delete();
        });

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
            'status_lowongan' => ['required', 'string'],
        ]);

        $lowongan = LowonganKerja::find($id);

        if (!$lowongan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lowongan kerja tidak ditemukan.',
            ], 404);
        }

        $lowongan->status_loker = strtolower($request->status_lowongan);
        if ($lowongan->status_loker === 'published' && !$lowongan->tanggal_tayang) {
            $lowongan->tanggal_tayang = now();
        }
        $lowongan->save();

        return response()->json([
            'status' => 'success',
            'message' => "Status lowongan berhasil diubah menjadi {$lowongan->status_loker}.",
            'data' => [
                'id' => $lowongan->id,
                'status_lowongan' => $lowongan->status_loker,
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
                // Nilai mengikuti data yang tersimpan di req_pk_loker
                'tipe_pekerjaan' => ['Full time', 'Part time', 'Contract', 'Internship'],
                'sistem_kerja' => ['WFO', 'WFH', 'Hybrid'],
                'jenis_kelamin' => ['Semua', 'Laki-laki', 'Perempuan'],
                'status_lowongan' => ['Draft', 'Published', 'Closed', 'Expired', 'Suspended', 'Blocked', 'Archived'],
                'tipe_keahlian' => ['wajib', 'tambahan'],
                'level_kemahiran' => ['pemula', 'menengah', 'ahli'],
            ],
        ]);
    }
}
