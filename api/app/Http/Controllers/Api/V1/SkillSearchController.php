<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkillSearchController extends Controller
{
    /**
     * GET /api/v1/skills/search
     * Pencarian cepat keahlian berdasarkan kata kunci
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

        $searchTerm = '%' . $query . '%';

        $type = $request->query('type', 'skill');
        $typeClause = ($type && $type !== 'all') ? "AND type = :type" : "";
        $params = [
            'term1' => $searchTerm,
            'term2' => $searchTerm,
            'term3' => $searchTerm,
            'term4' => $searchTerm,
        ];
        if ($type && $type !== 'all') {
            $params['type'] = $type;
        }

        // Hitung total hasil yang cocok
        $totalResult = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM skill_nodes
            WHERE (
                title ILIKE :term1
                OR title_en ILIKE :term2
                OR array_to_string(alt_labels, ' ') ILIKE :term3
                OR array_to_string(alt_labels_en, ' ') ILIKE :term4
            )
            {$typeClause}
        ", $params);

        $total = (int) ($totalResult->total ?? 0);

        // Ambil data hasil paginasi dengan ordering prioritas
        $queryParams = array_merge($params, [
            'exact' => $query,
            'prefix' => $query . '%',
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $skills = DB::select("
            SELECT
                id,
                title,
                title_en AS \"titleEn\",
                type
            FROM skill_nodes
            WHERE (
                title ILIKE :term1
                OR title_en ILIKE :term2
                OR array_to_string(alt_labels, ' ') ILIKE :term3
                OR array_to_string(alt_labels_en, ' ') ILIKE :term4
            )
            {$typeClause}
            ORDER BY
                CASE
                    WHEN LOWER(title) = LOWER(:exact) THEN 1
                    WHEN LOWER(title_en) = LOWER(:exact) THEN 2
                    WHEN title ILIKE :prefix THEN 3
                    WHEN title_en ILIKE :prefix THEN 4
                    ELSE 5
                END,
                title ASC
            LIMIT :limit OFFSET :offset
        ", $queryParams);

        $formattedData = array_map(function ($s) {
            return [
                'id' => (int) $s->id,
                'title' => $s->title,
                'titleEn' => $s->titleEn,
                'type' => $s->type,
            ];
        }, $skills);

        return response()->json([
            'status' => 'success',
            'query' => $query,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $formattedData,
        ]);
    }
}
