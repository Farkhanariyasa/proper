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
     * Tambah profil pencari kerja langsung ke tabel req_pk_pencaker
     */
    public function store(StoreJobSeekerRequest $request): JsonResponse
    {
        try {
            $mappedData = array_merge([
                // Nilai bawaan untuk kolom yang tidak diisi lewat form
                'kondisi_fisik' => 'Non Disabilitas',
                'rencana_kerja_luar_negeri' => 'Tidak',
                'bahasa' => 'Bahasa Indonesia',
            ], $this->mapFields($request->validated()), [
                'profile_id' => 'PROFILE-' . Str::lower(Str::random(9)),
                'start_date' => now()->format('d/m/Y'),
                'recent_start' => now()->format('d/m/Y'),
                'status_sekarang' => 'active',
                'tanggal_kedaluwarsa' => now()->addMonths(6)->format('d/m/Y'),
                'lamaran_diajukan' => 0,
            ]);

            $jobSeeker = JobSeeker::create($mappedData);
            $jobSeeker->load(['province:id,name', 'regency:id,name', 'educationLevel:id,name']);

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
     * Petakan input form (Tambah/Edit) ke kolom req_pk_pencaker.
     * Hanya kolom yang ada di input yang dipetakan (form Edit mengirim yang berubah saja).
     */
    private function mapFields(array $v): array
    {
        $m = [];

        if (array_key_exists('full_name', $v)) $m['name'] = trim($v['full_name']);
        if (array_key_exists('gender', $v)) $m['jenis_kelamin'] = $v['gender'];
        if (array_key_exists('umur', $v)) $m['umur'] = $v['umur'];

        // Wilayah: id master + nama dalam format data pencaker ("KAB. BOGOR", "DKI JAKARTA")
        if (!empty($v['regency_id'])) {
            $regency = Regency::with('province')->find($v['regency_id']);
            if ($regency) {
                $m['regency_id'] = $regency->id;
                $m['kab_kota'] = $this->normalizeRegionName($regency->name);
                $m['province_id'] = $regency->province_id;
                $m['provinsi'] = $this->normalizeRegionName($regency->province?->name);
                $m['region_name'] = $m['kab_kota'] . ', ' . $m['provinsi'];
            }
        } elseif (!empty($v['province_id'])) {
            $province = Province::find($v['province_id']);
            if ($province) {
                $m['province_id'] = $province->id;
                $m['provinsi'] = $this->normalizeRegionName($province->name);
            }
        }

        if (array_key_exists('education_level_id', $v)) {
            $m['education_level_id'] = $v['education_level_id'];
            $m['pendidikan'] = EducationLevel::find($v['education_level_id'])?->name;
        }

        foreach ([
            'marital', 'kondisi_fisik', 'jenis_disabilitas', 'status_bekerja', 'jurusan', 'nama_sekolah',
            'experience', 'sertifikasi', 'lembaga_pelatihan', 'progpel', 'keahlian', 'bahasa',
            'rencana_kerja_luar_negeri', 'country_wish',
        ] as $field) {
            if (array_key_exists($field, $v)) {
                $value = is_string($v[$field]) ? trim($v[$field]) : $v[$field];
                $m[$field] = $value === '' ? null : $value;
            }
        }

        // Jenis disabilitas & negara tujuan hanya relevan bila kondisinya terpenuhi
        if (($m['kondisi_fisik'] ?? null) === 'Non Disabilitas') $m['jenis_disabilitas'] = null;
        if (($m['rencana_kerja_luar_negeri'] ?? null) === 'Tidak') $m['country_wish'] = null;

        return $m;
    }

    /**
     * "Kabupaten Bogor" -> "KAB. BOGOR", "Kota Administrasi Jakarta Pusat" -> "KOTA ADM. JAKARTA PUSAT",
     * "Daerah Khusus Ibukota Jakarta" -> "DKI JAKARTA" (mengikuti penulisan data req_pk_pencaker)
     */
    private function normalizeRegionName(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }
        $name = mb_strtoupper(trim($name));
        $name = preg_replace('/^KABUPATEN ADMINISTRASI /', 'KAB. ADM. ', $name);
        $name = preg_replace('/^KABUPATEN /', 'KAB. ', $name);
        $name = preg_replace('/^KOTA ADMINISTRASI /', 'KOTA ADM. ', $name);
        return $name === 'DAERAH KHUSUS IBUKOTA JAKARTA' ? 'DKI JAKARTA' : $name;
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
     * Perbarui profil pencari kerja di tabel req_pk_pencaker (hanya kolom yang dikirim)
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

        try {
            $mappedData = $this->mapFields($request->validated());

            if (!empty($mappedData)) {
                $jobSeeker->update($mappedData);
            }

            $jobSeeker->load(['province:id,name', 'regency:id,name', 'educationLevel:id,name']);

            return response()->json([
                'status' => 'success',
                'message' => 'Profil pencari kerja berhasil diperbarui.',
                'data' => $jobSeeker,
            ]);
        } catch (Exception $e) {
            Log::error('Gagal memperbarui profil pencari kerja: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat memperbarui profil.',
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
