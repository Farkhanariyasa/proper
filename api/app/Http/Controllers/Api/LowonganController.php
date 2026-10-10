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
        $skillsData = $validated['skills'] ?? [];
        unset($validated['skills']);

        // Default creator
        if (!isset($validated['created_by']) && $request->user()) {
            $validated['created_by'] = $request->user()->id;
        }

        // Generate unik slug
        $baseSlug = Str::slug($validated['judul_lowongan'] . '-' . $validated['nama_perusahaan']);
        $validated['slug'] = $baseSlug . '-' . Str::lower(Str::random(5));

        try {
            $statusLoker = isset($validated['status_lowongan']) ? strtolower($validated['status_lowongan']) : 'draft';
            $tanggalTayang = $validated['tanggal_buka'] ?? null;
            if (!$tanggalTayang && $statusLoker === 'published') {
                $tanggalTayang = now();
            }

            $gajiTampilkan = $validated['gaji_tampilkan'] ?? true;
            $rentangGaji = null;
            if ($gajiTampilkan && (isset($validated['gaji_minimal']) || isset($validated['gaji_maksimal']))) {
                $rentangGaji = ($validated['gaji_minimal'] ?? '0') . '-' . ($validated['gaji_maksimal'] ?? '0');
            }

            $mappedData = [
                'job_id' => 'JOB-' . Str::random(4) . '-' . Str::random(4),
                'vac_id' => 'VAC-' . Str::random(4) . '-' . Str::random(4),
                'judul_pekerjaan' => $validated['judul_lowongan'] ?? null,
                'nama_perusahaan' => $validated['nama_perusahaan'] ?? null,
                'deskripsi_pekerjaan' => $validated['deskripsi_pekerjaan'] ?? null,
                'tipe_pekerjaan' => $validated['tipe_pekerjaan'] ?? null,
                'status_loker' => $statusLoker,
                'kuota' => $validated['jumlah_kebutuhan'] ?? null,
                'rentang_gaji' => $rentangGaji,
                'reg' => $validated['alamat_lengkap_penempatan'] ?? null,
                'provinsi_id' => $validated['provinsi_id'] ?? null,
                'regency_id' => $validated['regency_id'] ?? null,
                'kbji_2026_id' => $validated['kbji_id'] ?? null,
                'education_level_id' => $validated['education_level_id'] ?? null,
                'tanggal_tayang' => $tanggalTayang,
                'tanggal_expired_lowongan' => $validated['tanggal_tutup'] ?? null,
                'tanggal_dibuat' => now(),
            ];

            $lowongan = DB::transaction(function () use ($mappedData, $skillsData) {
                $item = LowonganKerja::create($mappedData);

                if (!empty($skillsData)) {
                    $hasLevelCol = \Illuminate\Support\Facades\Schema::hasColumn('lowongan_skills', 'level_kemahiran');
                    $insertSkills = [];
                    foreach ($skillsData as $sk) {
                        $level = in_array($sk['level_kemahiran'] ?? '', ['pemula', 'menengah', 'ahli']) 
                            ? $sk['level_kemahiran'] 
                            : 'menengah';
                        $skor = match($level) {
                            'ahli' => 1.0,
                            'pemula' => 0.4,
                            default => 0.7,
                        };

                        $row = [
                            'vac_id' => $item->vac_id,
                            'esco_skill_id' => $sk['esco_skill_id'],
                            'tipe_keahlian' => $sk['tipe_keahlian'] ?? 'wajib',
                            'skor' => $skor,
                            'metode' => 'input_manual',
                            'created_at' => now(),
                        ];

                        if ($hasLevelCol) {
                            $row['level_kemahiran'] = $level;
                        }

                        $insertSkills[] = $row;
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

            if (array_key_exists('tanggal_buka', $validated)) {
                $mappedData['tanggal_tayang'] = $validated['tanggal_buka'];
            }
            if (array_key_exists('tanggal_tutup', $validated)) {
                $mappedData['tanggal_expired_lowongan'] = $validated['tanggal_tutup'];
            }
            if (($mappedData['status_loker'] ?? '') === 'published' && !$lowongan->tanggal_tayang && empty($mappedData['tanggal_tayang'])) {
                $mappedData['tanggal_tayang'] = now();
            }

            if (array_key_exists('gaji_minimal', $validated) || array_key_exists('gaji_maksimal', $validated) || array_key_exists('gaji_tampilkan', $validated)) {
                $tampilkan = $validated['gaji_tampilkan'] ?? ($lowongan->rentang_gaji !== null);
                if ($tampilkan && (isset($validated['gaji_minimal']) || isset($validated['gaji_maksimal']))) {
                    $mappedData['rentang_gaji'] = ($validated['gaji_minimal'] ?? '0') . '-' . ($validated['gaji_maksimal'] ?? '0');
                } elseif (!$tampilkan) {
                    $mappedData['rentang_gaji'] = null;
                }
            }
            
            $mappedData['tanggal_update'] = now();

            DB::transaction(function () use ($lowongan, $mappedData, $hasSkills, $skillsData) {
                $lowongan->update($mappedData);

                if ($hasSkills && is_array($skillsData)) {
                    $vacId = $lowongan->vac_id;
                    if (!$vacId) {
                        $vacId = 'VAC-' . Str::random(4) . '-' . Str::random(4);
                        $lowongan->vac_id = $vacId;
                        $lowongan->save();
                    }

                    // Hapus skill lama untuk vac_id ini
                    DB::table('lowongan_skills')->where('vac_id', $vacId)->delete();

                    // Insert skill baru
                    $hasLevelCol = \Illuminate\Support\Facades\Schema::hasColumn('lowongan_skills', 'level_kemahiran');
                    $insertSkills = [];
                    foreach ($skillsData as $sk) {
                        $level = in_array($sk['level_kemahiran'] ?? '', ['pemula', 'menengah', 'ahli']) 
                            ? $sk['level_kemahiran'] 
                            : 'menengah';
                        $skor = match($level) {
                            'ahli' => 1.0,
                            'pemula' => 0.4,
                            default => 0.7,
                        };

                        $row = [
                            'vac_id' => $vacId,
                            'esco_skill_id' => $sk['esco_skill_id'],
                            'tipe_keahlian' => $sk['tipe_keahlian'] ?? 'wajib',
                            'skor' => $skor,
                            'metode' => 'input_manual',
                            'created_at' => now(),
                        ];

                        if ($hasLevelCol) {
                            $row['level_kemahiran'] = $level;
                        }

                        $insertSkills[] = $row;
                    }

                    if (!empty($insertSkills)) {
                        DB::table('lowongan_skills')->insert($insertSkills);
                    }
                }
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
            Log::error('Gagal memperbarui lowongan: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat memperbarui lowongan: ' . $e->getMessage(),
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
