<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard publik (landing page).
 *
 * Sumber data: req_pk_pencaker (pencari kerja) & req_pk_loker (lowongan).
 * Hanya mengembalikan angka agregat — tidak ada data pribadi (nama, ID profil).
 * Nama wilayah mengikuti penulisan tabel pencaker (huruf kapital, "KAB." / "KOTA ADM.").
 * Wilayah loker diambil dari kolom `reg`: "..., <Kab/Kota>, <Provinsi>".
 */
class PublicDashboardController extends Controller
{
    private const CACHE_TTL = 21600; // 6 jam — data sumber jarang berubah

    private const LOKER_PROVINCE = "UPPER(TRIM(reverse(split_part(reverse(reg), ',', 1))))";
    private const LOKER_REGENCY = "UPPER(TRIM(reverse(split_part(reverse(reg), ',', 2))))";

    // Filter tahun: loker = tahun tayang; pencaker = tahun pendaftaran/perpanjangan terakhir
    // (recent_start disimpan sebagai teks d/m/yyyy)
    private const LOKER_YEAR = 'EXTRACT(YEAR FROM tanggal_tayang)::int';
    private const PENCAKER_YEAR = "substring(recent_start from '([0-9]{4})$')::int";

    private const EDUCATION_ORDER = [
        'Tidak Tamat SD/Tidak Sekolah', 'SD atau Sederajat', 'SMP atau Sederajat',
        'SMA atau Sederajat', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'Profesi', 'S2', 'S3',
    ];

    private const AGE_GROUPS = [
        ['15-19', 15, 19], ['20-24', 20, 24], ['25-29', 25, 29], ['30-34', 30, 34],
        ['35-39', 35, 39], ['40-44', 40, 44], ['45-54', 45, 54], ['55+', 55, 70],
    ];

    /**
     * Kata kunci keterampilan yang dicari di judul & deskripsi lowongan
     * (sumber loker tidak memiliki kolom keterampilan terstruktur).
     */
    private const SKILL_KEYWORDS = [
        'Microsoft Excel' => '\\mexcel\\M',
        'Microsoft Office' => '(microsoft|ms\\.?) ?office',
        'Komunikasi' => 'komunikasi|communication',
        'Bahasa Inggris' => 'bahasa inggris|english',
        'Kerja Sama Tim' => 'kerja ?sama tim|team ?work|kerjasama',
        'Penjualan & Negosiasi' => 'negosiasi|negotiation|\\mselling\\M',
        'Kepemimpinan' => 'kepemimpinan|leadership',
        'SIM / Mengemudi' => '\\msim (a|b1|b2|c)\\M|mengemudi',
        'Komputer' => '\\mkomputer\\M|computer',
        'K3 / HSE' => '\\mk3\\M|\\mhse\\M|keselamatan kerja',
        'Akuntansi' => 'akuntansi|accounting',
        'AutoCAD' => 'autocad',
        'Analisis Data' => 'analisa data|analisis data|data analys',
        'Digital Marketing' => 'digital marketing|media sosial|social media',
        'Customer Service' => 'customer service|pelayanan pelanggan',
    ];

    /**
     * GET /api/public/dashboard/regions
     * Daftar provinsi beserta kabupaten/kotanya (dari data pencaker)
     */
    public function regions(): JsonResponse
    {
        $data = Cache::remember('public_dashboard:regions', self::CACHE_TTL, function () {
            $rows = DB::select("
                SELECT provinsi, kab_kota FROM req_pk_pencaker
                WHERE provinsi IS NOT NULL AND kab_kota IS NOT NULL
                GROUP BY provinsi, kab_kota
                ORDER BY provinsi, kab_kota
            ");

            $provinces = [];
            foreach ($rows as $row) {
                $provinces[$row->provinsi][] = $row->kab_kota;
            }

            return array_map(
                fn ($name, $regencies) => ['name' => $name, 'regencies' => $regencies],
                array_keys($provinces),
                $provinces
            );
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * GET /api/public/dashboard/years
     * Daftar tahun yang tersedia (gabungan tahun tayang loker & tahun pendaftaran pencaker), terbaru dulu
     */
    public function years(): JsonResponse
    {
        $data = Cache::remember('public_dashboard:years', self::CACHE_TTL, function () {
            $rows = DB::select('
                SELECT ' . self::LOKER_YEAR . ' AS tahun FROM req_pk_loker WHERE tanggal_tayang IS NOT NULL
                UNION
                SELECT ' . self::PENCAKER_YEAR . " AS tahun FROM req_pk_pencaker WHERE recent_start ~ '[0-9]{4}$'
                ORDER BY 1 DESC
            ");

            return array_map(fn ($r) => (int) $r->tahun, $rows);
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * GET /api/public/dashboard/ringkasan?tahun=&provinsi=&kab_kota=
     */
    public function ringkasan(Request $request): JsonResponse
    {
        return $this->cached('ringkasan', $request, function (array $area) {
            [$pWhere, $pParams] = $this->pencakerWhere($area);
            [$lWhere, $lParams] = $this->lokerWhere($area);

            $pencakerTotal = (int) DB::selectOne("SELECT COUNT(*) AS c FROM req_pk_pencaker WHERE {$pWhere}", $pParams)->c;
            // KPI Lowongan hanya menghitung loker yang sedang tayang (status_loker = 'published')
            $loker = DB::selectOne("
                SELECT COALESCE(SUM(kuota) FILTER (WHERE status_loker = 'published'), 0) AS kuota,
                       COALESCE(SUM(lamaran_diterima), 0) AS diterima
                FROM req_pk_loker WHERE {$lWhere}
            ", $lParams);

            // Top wilayah: provinsi (nasional) atau kab/kota (saat provinsi dipilih)
            $regionColumn = $area['provinsi'] ? 'kab_kota' : 'provinsi';

            return [
                'kpi' => [
                    'pencaker' => $pencakerTotal,
                    'lowongan_kuota' => (int) $loker->kuota,
                    'diterima' => (int) $loker->diterima,
                ],
                'status_bekerja' => $this->groupCount('req_pk_pencaker', 'status_bekerja', $pWhere, $pParams),
                'top_wilayah' => [
                    'level' => $regionColumn,
                    'items' => $this->groupCount('req_pk_pencaker', $regionColumn, $pWhere, $pParams, 6),
                ],
                'top_bidang' => $this->groupSum('bidang_pekerjaan', $lWhere, $lParams, 6),
                'top_industri' => $this->groupSum('industri', $lWhere, $lParams, 6),
            ];
        });
    }

    /**
     * GET /api/public/dashboard/profil-pencaker?tahun=&provinsi=&kab_kota=
     */
    public function profilPencaker(Request $request): JsonResponse
    {
        return $this->cached('profil', $request, function (array $area) {
            [$where, $params] = $this->pencakerWhere($area);

            $kpi = DB::selectOne("
                SELECT
                    COUNT(*) FILTER (WHERE pendidikan = 'SMK') AS smk,
                    COUNT(*) FILTER (WHERE pendidikan = 'S1') AS s1,
                    COUNT(*) FILTER (WHERE rencana_kerja_luar_negeri = 'Ya') AS minat_pmi,
                    COUNT(*) FILTER (WHERE kondisi_fisik = 'Disabilitas') AS disabilitas
                FROM req_pk_pencaker WHERE {$where}
            ", $params);

            // Distribusi pendidikan, diurutkan dari jenjang terendah
            $education = collect($this->groupCount('req_pk_pencaker', 'pendidikan', $where, $params))
                ->map(fn ($row) => ['label' => $row['label'] ?? 'Tidak diketahui', 'value' => $row['value']])
                ->sortBy(fn ($row) => array_search($row['label'], self::EDUCATION_ORDER, true) === false
                    ? 99
                    : array_search($row['label'], self::EDUCATION_ORDER, true))
                ->values()
                ->all(); // array biasa: objek Collection tidak bisa dibaca ulang dari cache

            // Kelompok umur per gender (umur tersimpan sebagai teks; abaikan nilai tidak wajar)
            $ageCase = implode(' ', array_map(
                fn ($g) => "WHEN usia BETWEEN {$g[1]} AND {$g[2]} THEN '{$g[0]}'",
                self::AGE_GROUPS
            ));
            $ageRows = DB::select("
                SELECT kelompok, jenis_kelamin, COUNT(*) AS c
                FROM (
                    SELECT CASE {$ageCase} END AS kelompok, jenis_kelamin
                    FROM (
                        SELECT jenis_kelamin,
                               CASE WHEN umur ~ '^[0-9]{1,2}$' THEN umur::int END AS usia
                        FROM req_pk_pencaker WHERE {$where}
                    ) AS umur_valid
                ) AS dikelompokkan
                WHERE kelompok IS NOT NULL
                GROUP BY 1, 2
            ", $params);
            $ages = [];
            foreach (self::AGE_GROUPS as [$label]) {
                $ages[$label] = ['label' => $label, 'laki_laki' => 0, 'perempuan' => 0];
            }
            foreach ($ageRows as $row) {
                $key = $row->jenis_kelamin === 'Perempuan' ? 'perempuan' : 'laki_laki';
                $ages[$row->kelompok][$key] += (int) $row->c;
            }

            $migrant = DB::select("
                SELECT country_wish AS label, COUNT(*) AS value
                FROM req_pk_pencaker
                WHERE {$where} AND COALESCE(country_wish, '') <> ''
                GROUP BY 1 ORDER BY 2 DESC LIMIT 8
            ", $params);

            $majors = DB::select("
                SELECT UPPER(TRIM(jurusan)) AS label, COUNT(*) AS value
                FROM req_pk_pencaker
                WHERE {$where} AND COALESCE(TRIM(jurusan), '') <> ''
                GROUP BY 1 ORDER BY 2 DESC LIMIT 10
            ", $params);

            return [
                'kpi' => [
                    'smk' => (int) $kpi->smk,
                    's1' => (int) $kpi->s1,
                    'minat_pmi' => (int) $kpi->minat_pmi,
                    'disabilitas' => (int) $kpi->disabilitas,
                ],
                'pendidikan' => $education,
                'umur_gender' => array_values($ages),
                'destinasi_pmi' => $this->formatRows($migrant),
                'top_jurusan' => $this->formatRows($majors),
            ];
        });
    }

    /**
     * GET /api/public/dashboard/kebutuhan-industri?tahun=&provinsi=&kab_kota=
     */
    public function kebutuhanIndustri(Request $request): JsonResponse
    {
        return $this->cached('industri', $request, function (array $area) {
            [$where, $params] = $this->lokerWhere($area);

            $kpi = DB::selectOne("
                SELECT COUNT(*) AS loker,
                       COUNT(DISTINCT nama_perusahaan) AS perusahaan,
                       COALESCE(SUM(kuota), 0) AS kuota,
                       COALESCE(AVG(kuota), 0) AS rata_kuota
                FROM req_pk_loker WHERE {$where}
            ", $params);

            $skillSelects = [];
            foreach (array_values(self::SKILL_KEYWORDS) as $i => $pattern) {
                $skillSelects[] = "COUNT(*) FILTER (WHERE (judul_pekerjaan || ' ' || COALESCE(deskripsi_pekerjaan, '')) ~* " . DB::getPdo()->quote($pattern) . ") AS s{$i}";
            }
            $skillRow = (array) DB::selectOne(
                'SELECT ' . implode(', ', $skillSelects) . " FROM req_pk_loker WHERE {$where}",
                $params
            );
            $skills = collect(array_keys(self::SKILL_KEYWORDS))
                ->map(fn ($label, $i) => ['label' => $label, 'value' => (int) $skillRow["s{$i}"]])
                ->filter(fn ($row) => $row['value'] > 0)
                ->sortByDesc('value')
                ->values()
                ->take(10)
                ->all();

            return [
                'kpi' => [
                    'loker' => (int) $kpi->loker,
                    'perusahaan' => (int) $kpi->perusahaan,
                    'kuota' => (int) $kpi->kuota,
                    'rata_kuota' => round((float) $kpi->rata_kuota, 1),
                ],
                'kuota_per_industri' => $this->groupSum('industri', $where, $params, 10),
                'pola_waktu_kerja' => $this->groupCount('req_pk_loker', 'tipe_pekerjaan', $where, $params),
                'keterampilan' => $skills,
            ];
        });
    }

    // ------------------------------------------------------------------

    private function cached(string $section, Request $request, callable $build): JsonResponse
    {
        $tahun = filter_var($request->query('tahun'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 2000, 'max_range' => 2100],
        ]);
        $provinsi = $this->cleanParam($request->query('provinsi'));
        $kabKota = $provinsi ? $this->cleanParam($request->query('kab_kota')) : null;
        $area = ['tahun' => $tahun ?: null, 'provinsi' => $provinsi, 'kab_kota' => $kabKota];

        $key = 'public_dashboard:' . $section . ':' . md5(json_encode($area));
        $data = Cache::remember($key, self::CACHE_TTL, fn () => $build($area));

        return response()->json(['status' => 'success', 'filter' => $area, 'data' => $data]);
    }

    private function cleanParam(mixed $value): ?string
    {
        $value = is_string($value) ? mb_strtoupper(trim($value)) : '';
        return $value === '' ? null : mb_substr($value, 0, 100);
    }

    private function pencakerWhere(array $area): array
    {
        $conditions = ['TRUE'];
        $params = [];
        if ($area['tahun']) {
            $conditions[] = self::PENCAKER_YEAR . ' = ?';
            $params[] = $area['tahun'];
        }
        if ($area['provinsi']) {
            $conditions[] = 'provinsi = ?';
            $params[] = $area['provinsi'];
        }
        if ($area['kab_kota']) {
            $conditions[] = 'kab_kota = ?';
            $params[] = $area['kab_kota'];
        }
        return [implode(' AND ', $conditions), $params];
    }

    private function lokerWhere(array $area): array
    {
        $conditions = ['TRUE'];
        $params = [];
        if ($area['tahun']) {
            $conditions[] = self::LOKER_YEAR . ' = ?';
            $params[] = $area['tahun'];
        }
        if ($area['provinsi']) {
            $conditions[] = self::LOKER_PROVINCE . ' = ?';
            $params[] = $area['provinsi'];
        }
        if ($area['kab_kota']) {
            $conditions[] = self::LOKER_REGENCY . ' = ?';
            $params[] = $area['kab_kota'];
        }
        return [implode(' AND ', $conditions), $params];
    }

    private function groupCount(string $table, string $column, string $where, array $params, ?int $limit = null): array
    {
        $rows = DB::select("
            SELECT {$column} AS label, COUNT(*) AS value
            FROM {$table} WHERE {$where}
            GROUP BY 1 ORDER BY 2 DESC" . ($limit ? " LIMIT {$limit}" : ''), $params);

        return $this->formatRows($rows);
    }

    private function groupSum(string $column, string $where, array $params, int $limit): array
    {
        $rows = DB::select("
            SELECT {$column} AS label, SUM(kuota) AS value
            FROM req_pk_loker
            WHERE {$where} AND COALESCE({$column}, '') <> ''
            GROUP BY 1 ORDER BY 2 DESC NULLS LAST LIMIT {$limit}
        ", $params);

        return $this->formatRows($rows);
    }

    private function formatRows(array $rows): array
    {
        return array_map(fn ($r) => ['label' => $r->label, 'value' => (int) $r->value], $rows);
    }
}
