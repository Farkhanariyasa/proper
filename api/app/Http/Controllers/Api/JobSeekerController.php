<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobSeekerRequest;
use App\Http\Requests\UpdateJobSeekerRequest;
use App\Models\JobSeeker;
use App\Models\Province;
use App\Models\Regency;
use App\Models\EducationLevel;
use App\Models\SkillNode;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $currentPage = $jobSeekers->currentPage();
        $hasMore = $jobSeekers->hasMorePages();
        $lastPage = $hasMore ? $currentPage + 1 : $currentPage;

        return response()->json([
            'status' => 'success',
            'data' => $jobSeekers->items(),
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $jobSeekers->perPage(),
                'total' => $lastPage * $jobSeekers->perPage(),
                'has_more_pages' => $hasMore,
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
        $skillIds = $validated['skills'] ?? [];
        unset($validated['skills']);

        // Default created_by to current authenticated user if available
        if (!isset($validated['created_by']) && $request->user()) {
            $validated['created_by'] = $request->user()->id;
        }

        try {
            // Lookup Wilayah
            $province = null;
            $regency = null;
            if (!empty($validated['regency_id'])) {
                $regency = Regency::with('province')->find($validated['regency_id']);
                $province = $regency?->province;
            }

            // Lookup Jenjang Pendidikan
            $educationLevel = null;
            if (!empty($validated['education_level_id'])) {
                $educationLevel = EducationLevel::find($validated['education_level_id']);
            }

            // Hitung umur dari tanggal lahir
            $umur = null;
            if (!empty($validated['birth_date'])) {
                $umur = \Carbon\Carbon::parse($validated['birth_date'])->age;
            }

            $jenisKelamin = ($validated['gender'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan';

            // Ekstraksi sertifikasi & pelatihan dari array menjadi string
            $sertifikasi = null;
            if (!empty($validated['certifications']) && is_array($validated['certifications'])) {
                $certNames = array_filter(array_column($validated['certifications'], 'name'));
                $sertifikasi = !empty($certNames) ? implode(', ', $certNames) : null;
            }

            $pelatihan = null;
            if (!empty($validated['trainings']) && is_array($validated['trainings'])) {
                $trainNames = array_filter(array_column($validated['trainings'], 'name'));
                $pelatihan = !empty($trainNames) ? implode(', ', $trainNames) : null;
            }

            // Ringkasan keahlian teks dari skill ESCO yang dipilih
            $skillNames = [];
            if (!empty($skillIds)) {
                $skillNames = SkillNode::whereIn('id', $skillIds)->pluck('title')->toArray();
            }
            $keahlian = !empty($skillNames) ? implode(', ', array_slice($skillNames, 0, 5)) : null;

            $mappedData = [
                'profile_id' => 'PROFILE-' . Str::lower(Str::random(4)) . '-' . Str::lower(Str::random(4)),
                'name' => $validated['full_name'],
                'provinsi' => $province?->name,
                'province_id' => $province?->id,
                'kab_kota' => $regency?->name,
                'regency_id' => $regency?->id,
                'region_name' => ($regency?->name ? $regency->name . ', ' : '') . ($province?->name ?? ''),
                'umur' => $umur,
                'jenis_kelamin' => $jenisKelamin,
                'kondisi_fisik' => 'Non Disabilitas',
                'marital' => 'BELUM MENIKAH',
                'status_bekerja' => ($validated['experience_range'] ?? '') === 'fresh_graduate' ? 'Fresh Graduate' : 'Mencari Kerja',
                'start_date' => now()->format('d/m/Y'),
                'recent_start' => now()->format('d/m/Y'),
                'status_sekarang' => 'active',
                'tanggal_kedaluwarsa' => now()->addMonths(6)->format('d/m/Y'),
                'pendidikan' => $educationLevel?->name,
                'education_level_id' => $validated['education_level_id'],
                'jurusan' => $validated['study_field_detail'] ?? $validated['study_field_group'] ?? null,
                'experience' => $validated['experience_range'] ?? null,
                'sertifikasi' => $sertifikasi,
                'progpel' => $pelatihan,
                'keahlian' => $keahlian,
                'bahasa' => 'Bahasa Indonesia',
                'rencana_kerja_luar_negeri' => 'Tidak',
                'lamaran_diajukan' => 0,
            ];

            $jobSeeker = DB::transaction(function () use ($mappedData, $skillIds) {
                // 1. Insert data profil pencari kerja ke req_pk_pencaker
                $seeker = JobSeeker::create($mappedData);

                // 2. Hubungkan keahlian terpilih ke pivot pencaker_esco_skills
                if (!empty($skillIds)) {
                    $syncData = [];
                    foreach ($skillIds as $sId) {
                        $syncData[$sId] = [
                            'is_manual' => true,
                            'source' => 'manual',
                        ];
                    }
                    $seeker->skills()->sync($syncData);
                }

                return $seeker;
            });

            // Load relasi lengkap untuk response
            $jobSeeker->load([
                'province:id,name',
                'regency:id,name',
                'educationLevel:id,name',
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
                'message' => 'Terjadi kesalahan sistem saat menyimpan profil pencari kerja: ' . $e->getMessage(),
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
     * PUT /api/job-seekers/{id}
     * Perbarui data profil pencari kerja
     */
    public function update(UpdateJobSeekerRequest $request, $id): JsonResponse
    {
        $jobSeeker = JobSeeker::find($id);

        if (!$jobSeeker) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil pencari kerja tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validated();
        $hasSkills = array_key_exists('skills', $validated);
        $skillIds = $hasSkills ? $validated['skills'] : null;
        unset($validated['skills']);

        try {
            $mappedData = [];

            if (array_key_exists('full_name', $validated)) {
                $mappedData['name'] = $validated['full_name'];
            } elseif (array_key_exists('name', $validated)) {
                $mappedData['name'] = $validated['name'];
            }

            if (array_key_exists('gender', $validated)) {
                $mappedData['jenis_kelamin'] = in_array($validated['gender'], ['L', 'Laki-laki']) ? 'Laki-laki' : 'Perempuan';
            } elseif (array_key_exists('jenis_kelamin', $validated)) {
                $mappedData['jenis_kelamin'] = $validated['jenis_kelamin'];
            }

            if (array_key_exists('birth_date', $validated) && !empty($validated['birth_date'])) {
                $mappedData['umur'] = \Carbon\Carbon::parse($validated['birth_date'])->age;
            } elseif (array_key_exists('umur', $validated)) {
                $mappedData['umur'] = $validated['umur'];
            }

            if (array_key_exists('province_id', $validated)) {
                $mappedData['province_id'] = $validated['province_id'];
                $prov = Province::find($validated['province_id']);
                if ($prov) $mappedData['provinsi'] = $prov->name;
            }

            if (array_key_exists('regency_id', $validated)) {
                $mappedData['regency_id'] = $validated['regency_id'];
                $reg = Regency::find($validated['regency_id']);
                if ($reg) {
                    $mappedData['kab_kota'] = $reg->name;
                    if (!isset($mappedData['province_id']) && $reg->province_id) {
                        $mappedData['province_id'] = $reg->province_id;
                        $prov = Province::find($reg->province_id);
                        if ($prov) $mappedData['provinsi'] = $prov->name;
                    }
                }
            }

            if (array_key_exists('education_level_id', $validated)) {
                $mappedData['education_level_id'] = $validated['education_level_id'];
                $edu = EducationLevel::find($validated['education_level_id']);
                if ($edu) $mappedData['pendidikan'] = $edu->name;
            }

            if (array_key_exists('jurusan', $validated)) $mappedData['jurusan'] = $validated['jurusan'];
            if (array_key_exists('nama_sekolah', $validated)) $mappedData['nama_sekolah'] = $validated['nama_sekolah'];
            if (array_key_exists('experience', $validated)) $mappedData['experience'] = $validated['experience'];
            if (array_key_exists('keahlian', $validated)) $mappedData['keahlian'] = $validated['keahlian'];
            if (array_key_exists('sertifikasi', $validated)) $mappedData['sertifikasi'] = $validated['sertifikasi'];
            if (array_key_exists('lembaga_pelatihan', $validated)) $mappedData['lembaga_pelatihan'] = $validated['lembaga_pelatihan'];
            if (array_key_exists('progpel', $validated)) $mappedData['progpel'] = $validated['progpel'];
            if (array_key_exists('status_bekerja', $validated)) $mappedData['status_bekerja'] = $validated['status_bekerja'];

            DB::transaction(function () use ($jobSeeker, $mappedData, $hasSkills, $skillIds) {
                if (!empty($mappedData)) {
                    $jobSeeker->update($mappedData);
                }

                if ($hasSkills && is_array($skillIds)) {
                    $syncData = [];
                    foreach ($skillIds as $sId) {
                        $syncData[$sId] = [
                            'is_manual' => true,
                            'source' => 'manual',
                        ];
                    }
                    $jobSeeker->skills()->sync($syncData);
                }
            });

            $jobSeeker->load([
                'province:id,name',
                'regency:id,name',
                'educationLevel:id,name',
                'skills:id,code,title,title_en,type',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profil pencari kerja berhasil diperbarui.',
                'data' => $jobSeeker,
            ]);
        } catch (Exception $e) {
            Log::error('Gagal memperbarui profil pencari kerja: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat memperbarui profil: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/job-seekers/{id}
     * Hapus profil pencari kerja dan relasi skill
     */
    public function destroy($id): JsonResponse
    {
        $jobSeeker = JobSeeker::find($id);

        if (!$jobSeeker) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil pencari kerja tidak ditemukan.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($jobSeeker) {
                // Detach pivot skills
                $jobSeeker->skills()->detach();
                // Hapus data pencaker
                $jobSeeker->delete();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Profil pencari kerja berhasil dihapus.',
            ]);
        } catch (Exception $e) {
            Log::error('Gagal menghapus profil pencari kerja: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menghapus profil pencari kerja: ' . $e->getMessage(),
            ], 500);
        }
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
        $extraction = $extractor->extractForJobSeeker($jobSeeker);

        return response()->json([
            'status' => 'success',
            'data' => $extraction['skills'],
            'meta' => [
                'keywords' => $extraction['keywords'],
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
     * Ekstraksi ulang skill dari keahlian, experience, dan sertifikasi secara autentik
     */
    public function extractSkills(Request $request, $id): JsonResponse
    {
        $jobSeeker = JobSeeker::find($id);

        if (!$jobSeeker) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $extractor = app(\App\Services\CandidateSkillExtractorService::class);
        $extractor->syncSkillsForJobSeeker($jobSeeker, true);
        $extraction = $extractor->extractForJobSeeker($jobSeeker);

        return response()->json([
            'status' => 'success',
            'message' => 'Skill berhasil diekstraksi langsung dari keahlian, pengalaman, dan sertifikasi.',
            'data' => $extraction['skills'],
            'meta' => [
                'keywords' => $extraction['keywords'],
            ],
        ]);
    }

    /**
     * POST /api/job-seekers/{id}/skills
     * Update/Overwrite skill pencaker oleh operator (Bebas ID Taksonomi)
     */
    public function updateSkills(Request $request, $id): JsonResponse
    {
        $request->validate([
            'skills' => 'required|array',
        ]);

        $jobSeeker = JobSeeker::find($id);

        if (!$jobSeeker) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $intSkills = [];
        $textSkills = [];

        foreach ($request->skills as $sk) {
            if (is_numeric($sk)) {
                $intSkills[] = (int) $sk;
            } elseif (is_string($sk) && trim($sk) !== '') {
                $textSkills[] = trim($sk);
            }
        }

        // Simpan node ID yang valid ke pivot
        if (!empty($intSkills)) {
            $validNodes = \App\Models\SkillNode::whereIn('id', $intSkills)->pluck('id')->all();
            $syncData = [];
            foreach ($validNodes as $skillId) {
                $syncData[$skillId] = [
                    'is_manual' => true,
                    'source' => 'manual',
                ];
            }
            $jobSeeker->skills()->sync($syncData);
        }

        // Simpan free-text skill ke kolom keahlian profil
        if (!empty($textSkills)) {
            $existing = array_filter(array_map('trim', explode(',', $jobSeeker->keahlian ?? '')));
            $merged = array_unique(array_merge($existing, $textSkills));
            $jobSeeker->keahlian = implode(', ', $merged);
            $jobSeeker->save();
        }

        $extractor = app(\App\Services\CandidateSkillExtractorService::class);
        $extraction = $extractor->extractForJobSeeker($jobSeeker);

        return response()->json([
            'status' => 'success',
            'message' => 'Keahlian pencari kerja berhasil diperbarui',
            'data' => $extraction['skills'],
        ]);
    }
}
