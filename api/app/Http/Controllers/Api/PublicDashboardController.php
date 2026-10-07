<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\LockTimeoutException;
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
    private const CACHE_TTL = 86400; // 24 jam — data sumber jarang berubah
    private const CACHE_TTL_METADATA = 604800; // 7 hari untuk years & regions

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
        $data = Cache::remember('public_dashboard:regions', self::CACHE_TTL_METADATA, function () {
            return $this->buildRegions();
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function buildRegions(): array
    {
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
    }

    /**
     * GET /api/public/dashboard/years
     * Daftar tahun yang tersedia (gabungan tahun tayang loker & tahun pendaftaran pencaker), terbaru dulu
     */
    public function years(): JsonResponse
    {
        $data = Cache::remember('public_dashboard:years', self::CACHE_TTL_METADATA, function () {
            return $this->buildYears();
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function buildYears(): array
    {
        $rows = DB::select('
            SELECT ' . self::LOKER_YEAR . ' AS tahun FROM req_pk_loker WHERE tanggal_tayang IS NOT NULL
            UNION
            SELECT ' . self::PENCAKER_YEAR . " AS tahun FROM req_pk_pencaker WHERE recent_start ~ '[0-9]{4}$'
            ORDER BY 1 DESC
        ");

        return array_map(fn ($r) => (int) $r->tahun, $rows);
    }

    /**
     * GET /api/public/dashboard/ringkasan?tahun=&provinsi=&kab_kota=
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $payload = $this->cached('ringkasan', $request, fn (array $area) => $this->buildRingkasan($area))->getData(true);

        // Data lowongan per status dihitung & di-cache terpisah (hanya req_pk_loker, ringan),
        // agar cache ringkasan yang sudah ada tidak perlu dihitung ulang (memindai tabel pencaker).
        // KPI "Lowongan" diambil dari data yang sama supaya selalu konsisten dengan kartu status.
        $area = $payload['filter'];
        $statuses = Cache::remember(
            'public_dashboard:status_loker_v2:' . md5(json_encode($area)),
            self::CACHE_TTL,
            fn () => $this->buildStatusLoker($area)
        );

        $tayang = collect($statuses)->firstWhere('label', 'published');
        $payload['data']['kpi']['lowongan_kuota'] = $tayang['kuota'] ?? 0;
        $payload['data']['kpi']['lowongan_tayang'] = $tayang['value'] ?? 0;
        $payload['data']['status_loker'] = $statuses;

        return response()->json($payload);
    }

    /**
     * Jumlah lowongan (value) dan total kuota per status_loker
     */
    public function buildStatusLoker(array $area): array
    {
        [$where, $params] = $this->lokerWhere($area);
        // Data impor memakai 'published', lowongan dari aplikasi memakai 'tayang' -> disatukan
        $rows = DB::select("
            SELECT CASE WHEN LOWER(status_loker) IN ('published', 'tayang') THEN 'published'
                        ELSE LOWER(status_loker) END AS label,
                   COUNT(*) AS value,
                   COALESCE(SUM(kuota), 0) AS kuota
            FROM req_pk_loker WHERE {$where}
            GROUP BY 1 ORDER BY 2 DESC
        ", $params);

        return array_map(fn ($r) => [
            'label' => $r->label,
            'value' => (int) $r->value,
            'kuota' => (int) $r->kuota,
        ], $rows);
    }

    public function buildRingkasan(array $area): array
    {
        [$pWhere, $pParams] = $this->pencakerWhere($area);
        [$lWhere, $lParams] = $this->lokerWhere($area);

        // Top wilayah: provinsi (nasional) atau kab/kota (saat provinsi dipilih)
        $regionColumn = $area['provinsi'] ? 'kab_kota' : 'provinsi';

        // Total, status bekerja, dan top wilayah pencaker dalam SATU pemindaian (GROUPING SETS)
        $rows = DB::select("
            SELECT GROUPING(status_bekerja) AS g_status, GROUPING({$regionColumn}) AS g_wilayah,
                   status_bekerja, {$regionColumn} AS wilayah, COUNT(*) AS c
            FROM req_pk_pencaker WHERE {$pWhere}
            GROUP BY GROUPING SETS ((), (status_bekerja), ({$regionColumn}))
        ", $pParams);

        $pencakerTotal = 0;
        $statusBekerja = [];
        $wilayah = [];
        foreach ($rows as $row) {
            if ($row->g_status && $row->g_wilayah) {
                $pencakerTotal = (int) $row->c;
            } elseif (!$row->g_status) {
                $statusBekerja[] = ['label' => $row->status_bekerja, 'value' => (int) $row->c];
            } else {
                $wilayah[] = ['label' => $row->wilayah, 'value' => (int) $row->c];
            }
        }
        usort($statusBekerja, fn ($a, $b) => $b['value'] <=> $a['value']);
        usort($wilayah, fn ($a, $b) => $b['value'] <=> $a['value']);

        // KPI Lowongan hanya menghitung loker yang sedang tayang (status_loker = 'published')
        $loker = DB::selectOne("
            SELECT COALESCE(SUM(kuota) FILTER (WHERE LOWER(status_loker) IN ('published', 'tayang')), 0) AS kuota,
                   COALESCE(SUM(lamaran_diterima), 0) AS diterima
            FROM req_pk_loker WHERE {$lWhere}
        ", $lParams);

        return [
            'kpi' => [
                'pencaker' => $pencakerTotal,
                'lowongan_kuota' => (int) $loker->kuota,
                'diterima' => (int) $loker->diterima,
            ],
            'status_bekerja' => $statusBekerja,
            'top_wilayah' => [
                'level' => $regionColumn,
                'items' => array_slice($wilayah, 0, 6),
            ],
            'top_bidang' => $this->groupSum('bidang_pekerjaan', $lWhere, $lParams, 6),
            'top_industri' => $this->groupSum('industri', $lWhere, $lParams, 6),
        ];
    }

    /**
     * GET /api/public/dashboard/profil-pencaker?tahun=&provinsi=&kab_kota=
     */
    public function profilPencaker(Request $request): JsonResponse
    {
        return $this->cached('profil', $request, fn (array $area) => $this->buildProfilPencaker($area));
    }

    public function buildProfilPencaker(array $area): array
    {
        [$where, $params] = $this->pencakerWhere($area);

        // Semua agregat profil dalam SATU pemindaian tabel pencaker (GROUPING SETS):
        // total + KPI, pendidikan, umur x gender, negara tujuan, jurusan.
        // Negara & jurusan dibatasi 12 teratas di SQL agar hasil yang dikirim tetap kecil.
        $ageCase = implode(' ', array_map(
            fn ($g) => "WHEN usia BETWEEN {$g[1]} AND {$g[2]} THEN '{$g[0]}'",
            self::AGE_GROUPS
        ));
        $rows = DB::select("
            WITH base AS (
                SELECT pendidikan, jenis_kelamin,
                       CASE {$ageCase} END AS kelompok,
                       NULLIF(country_wish, '') AS negara,
                       NULLIF(UPPER(TRIM(jurusan)), '') AS jurusan,
                       rencana_kerja_luar_negeri, kondisi_fisik
                FROM (
                    SELECT pendidikan, jenis_kelamin, country_wish, jurusan,
                           rencana_kerja_luar_negeri, kondisi_fisik,
                           CASE WHEN umur ~ '^[0-9]{1,2}$' THEN umur::int END AS usia
                    FROM req_pk_pencaker WHERE {$where}
                ) AS p
            ),
            agg AS (
                SELECT GROUPING(pendidikan) AS g_pend, GROUPING(kelompok) AS g_umur,
                       GROUPING(negara) AS g_negara, GROUPING(jurusan) AS g_jurusan,
                       pendidikan, kelompok, jenis_kelamin, negara, jurusan,
                       COUNT(*) AS c,
                       COUNT(*) FILTER (WHERE rencana_kerja_luar_negeri = 'Ya') AS minat_pmi,
                       COUNT(*) FILTER (WHERE kondisi_fisik = 'Disabilitas') AS disabilitas
                FROM base
                GROUP BY GROUPING SETS ((), (pendidikan), (kelompok, jenis_kelamin), (negara), (jurusan))
            )
            SELECT * FROM (
                SELECT agg.*, ROW_NUMBER() OVER (
                    PARTITION BY g_pend, g_umur, g_negara, g_jurusan ORDER BY c DESC
                ) AS rn
                FROM agg
            ) AS ranked
            WHERE NOT ((g_negara = 0 OR g_jurusan = 0) AND rn > 12)
        ", $params);

        $kpi = (object) ['minat_pmi' => 0, 'disabilitas' => 0];
        $educationRaw = $ageRows = $migrantRows = $majorRows = [];
        foreach ($rows as $row) {
            if ($row->g_pend && $row->g_umur && $row->g_negara && $row->g_jurusan) {
                $kpi = $row; // baris total
            } elseif (!$row->g_pend) {
                $educationRaw[] = ['label' => $row->pendidikan, 'value' => (int) $row->c];
            } elseif (!$row->g_umur) {
                if ($row->kelompok !== null) {
                    $ageRows[] = $row;
                }
            } elseif (!$row->g_negara) {
                if ($row->negara !== null) {
                    $migrantRows[] = (object) ['label' => $row->negara, 'value' => (int) $row->c];
                }
            } elseif ($row->jurusan !== null) {
                $majorRows[] = (object) ['label' => $row->jurusan, 'value' => (int) $row->c];
            }
        }
        $byValue = fn ($a, $b) => (is_array($b) ? $b['value'] : $b->value) <=> (is_array($a) ? $a['value'] : $a->value);
        usort($educationRaw, $byValue);
        usort($migrantRows, $byValue);
        usort($majorRows, $byValue);
        $migrant = array_slice($migrantRows, 0, 8);
        $majors = array_slice($majorRows, 0, 10);

        // Distribusi pendidikan, diurutkan dari jenjang terendah
        $education = collect($educationRaw)
            ->map(fn ($row) => ['label' => $row['label'] ?? 'Tidak diketahui', 'value' => $row['value']])
            ->sortBy(fn ($row) => array_search($row['label'], self::EDUCATION_ORDER, true) === false
                ? 99
                : array_search($row['label'], self::EDUCATION_ORDER, true))
            ->values()
            ->all();

        // Hitung SMK dan S1 langsung dari agregat pendidikan untuk menghemat komputasi
        $smkCount = 0;
        $s1Count = 0;
        foreach ($educationRaw as $item) {
            $label = $item['label'] ?? '';
            if ($label === 'SMK') {
                $smkCount = (int) $item['value'];
            } elseif ($label === 'S1') {
                $s1Count = (int) $item['value'];
            }
        }

        // Kelompok umur per gender (umur tersimpan sebagai teks; nilai tidak wajar diabaikan)
        $ages = [];
        foreach (self::AGE_GROUPS as [$label]) {
            $ages[$label] = ['label' => $label, 'laki_laki' => 0, 'perempuan' => 0];
        }
        foreach ($ageRows as $row) {
            $key = $row->jenis_kelamin === 'Perempuan' ? 'perempuan' : 'laki_laki';
            $ages[$row->kelompok][$key] += (int) $row->c;
        }

        return [
            'kpi' => [
                'smk' => $smkCount,
                's1' => $s1Count,
                'minat_pmi' => (int) $kpi->minat_pmi,
                'disabilitas' => (int) $kpi->disabilitas,
            ],
            'pendidikan' => $education,
            'umur_gender' => array_values($ages),
            'destinasi_pmi' => $this->formatRows($migrant),
            'top_jurusan' => $this->formatRows($majors),
        ];
    }

    /**
     * GET /api/public/dashboard/kebutuhan-industri?tahun=&provinsi=&kab_kota=
     */
    public function kebutuhanIndustri(Request $request): JsonResponse
    {
        return $this->cached('industri', $request, fn (array $area) => $this->buildKebutuhanIndustri($area));
    }

    public function buildKebutuhanIndustri(array $area): array
    {
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
        $data = Cache::get($key);

        if ($data === null) {
            // Kunci per kombinasi filter: bila beberapa pengunjung membuka filter yang sama
            // saat cache kosong, hanya satu yang menghitung; yang lain menunggu hasilnya.
            try {
                $data = Cache::lock($key . ':lock', 120)->block(90, function () use ($key, $build, $area) {
                    return Cache::get($key) ?? tap($build($area), fn ($fresh) => Cache::put($key, $fresh, self::CACHE_TTL));
                });
            } catch (LockTimeoutException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data sedang disiapkan, silakan coba beberapa saat lagi.',
                ], 503);
            }
        }

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
