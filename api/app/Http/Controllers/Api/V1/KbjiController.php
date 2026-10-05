<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KbjiController extends Controller
{
    /**
     * Kolom dasar + flag apakah node memiliki turunan
     */
    private const NODE_COLUMNS = "
        k.id,
        k.code,
        k.title,
        k.level,
        k.isco_code AS \"iscoCode\",
        EXISTS(SELECT 1 FROM kbji_classifications c WHERE c.parent_code = k.code) AS \"hasChildren\"
    ";

    /**
     * GET /api/v1/kbji
     * Mengambil 10 Golongan Pokok (major_group), urutan numerik 0 -> 9
     */
    public function index(): JsonResponse
    {
        $rows = DB::select("
            SELECT " . self::NODE_COLUMNS . "
            FROM kbji_classifications k
            WHERE k.level = 'major_group'
            ORDER BY k.code::int
        ");

        return response()->json([
            'status' => 'success',
            'data' => array_map([$this, 'formatNode'], $rows),
        ]);
    }

    /**
     * GET /api/v1/kbji/{code}/children
     * Drill-down turunan langsung suatu kode KBJI
     */
    public function children(string $code): JsonResponse
    {
        $parent = DB::table('kbji_classifications')
            ->where('code', $code)
            ->first(['code', 'title', 'level']);

        if (!$parent) {
            return $this->notFound();
        }

        $rows = DB::select("
            SELECT " . self::NODE_COLUMNS . "
            FROM kbji_classifications k
            WHERE k.parent_code = :code
            ORDER BY k.code
        ", ['code' => $code]);

        $children = array_map([$this, 'formatNode'], $rows);

        return response()->json([
            'status' => 'success',
            'parent' => [
                'code' => $parent->code,
                'title' => $parent->title,
                'level' => $parent->level,
            ],
            'totalChildren' => count($children),
            'children' => $children,
        ]);
    }

    /**
     * GET /api/v1/kbji/{code}
     * Detail lengkap satu kode KBJI beserta jalur induk (breadcrumb) dan turunannya
     */
    public function show(string $code): JsonResponse
    {
        $node = DB::table('kbji_classifications')
            ->where('code', $code)
            ->first(['code', 'title', 'level', 'parent_code', 'description', 'isco_code']);

        if (!$node) {
            return $this->notFound();
        }

        // Jalur induk dari Golongan Pokok hingga induk langsung
        $ancestors = DB::select("
            WITH RECURSIVE chain AS (
                SELECT code, title, level, parent_code, 1 AS depth
                FROM kbji_classifications
                WHERE code = :parent_code
                UNION ALL
                SELECT p.code, p.title, p.level, p.parent_code, chain.depth + 1
                FROM kbji_classifications p
                JOIN chain ON p.code = chain.parent_code
                WHERE chain.depth < 10
            )
            SELECT code, title, level FROM chain ORDER BY depth DESC
        ", ['parent_code' => $node->parent_code]);

        $children = DB::select("
            SELECT " . self::NODE_COLUMNS . "
            FROM kbji_classifications k
            WHERE k.parent_code = :code
            ORDER BY k.code
        ", ['code' => $code]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'code' => $node->code,
                'title' => $node->title,
                'level' => $node->level,
                'parentCode' => $node->parent_code,
                'description' => $node->description,
                'iscoCode' => $node->isco_code,
                'ancestors' => array_map(fn ($a) => [
                    'code' => $a->code,
                    'title' => $a->title,
                    'level' => $a->level,
                ], $ancestors),
                'children' => array_map([$this, 'formatNode'], $children),
            ],
        ]);
    }

    /**
     * GET /api/v1/kbji/search?q=
     * Pencarian berdasarkan kode atau judul jabatan
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter query "q" wajib diisi',
            ], 422);
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(1, (int) $request->query('limit', 20)));
        $offset = ($page - 1) * $limit;

        $params = [
            'term' => '%' . $query . '%',
            'code_prefix' => $query . '%',
        ];
        $where = "(k.title ILIKE :term OR k.code LIKE :code_prefix)";

        $total = (int) (DB::selectOne("
            SELECT COUNT(*) AS total FROM kbji_classifications k WHERE {$where}
        ", $params)->total ?? 0);

        $rows = DB::select("
            SELECT " . self::NODE_COLUMNS . "
            FROM kbji_classifications k
            WHERE {$where}
            ORDER BY
                CASE
                    WHEN k.code = :exact_code THEN 1
                    WHEN LOWER(k.title) = LOWER(:exact_title) THEN 2
                    WHEN k.title ILIKE :prefix THEN 3
                    ELSE 4
                END,
                k.code
            LIMIT :limit OFFSET :offset
        ", array_merge($params, [
            'exact_code' => $query,
            'exact_title' => $query,
            'prefix' => $query . '%',
            'limit' => $limit,
            'offset' => $offset,
        ]));

        return response()->json([
            'status' => 'success',
            'query' => $query,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => array_map([$this, 'formatNode'], $rows),
        ]);
    }

    /**
     * GET /api/v1/kbji/stats
     * Jumlah entri KBJI per level hierarki
     */
    public function stats(): JsonResponse
    {
        $counts = DB::table('kbji_classifications')
            ->select('level', DB::raw('COUNT(*) AS total'))
            ->groupBy('level')
            ->pluck('total', 'level');

        $levels = ['major_group', 'sub_major_group', 'minor_group', 'unit_group', 'occupation'];
        $byLevel = [];
        foreach ($levels as $level) {
            $byLevel[$level] = (int) ($counts[$level] ?? 0);
        }

        return response()->json([
            'status' => 'success',
            'version' => 'KBJI 2020',
            'total' => array_sum($byLevel),
            'byLevel' => $byLevel,
        ]);
    }

    private function formatNode(object $row): array
    {
        return [
            'id' => isset($row->id) ? (int) $row->id : null,
            'code' => $row->code,
            'title' => $row->title,
            'level' => $row->level,
            'iscoCode' => $row->iscoCode,
            'hasChildren' => (bool) $row->hasChildren,
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Kode KBJI tidak ditemukan',
        ], 404);
    }
}
