<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EscoSkillController extends Controller
{
    /**
     * GET /api/esco-skills?q=...
     * Autocomplete pencarian skill ESCO
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        if ($query === '') {
            $skills = DB::table('skill_nodes')
                ->select('id', 'code', 'title', 'title_en', 'type')
                ->where('type', 'skill')
                ->orderBy('title', 'asc')
                ->limit($limit)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $skills,
            ]);
        }

        $searchTerm = '%' . $query . '%';

        $skills = DB::select("
            SELECT
                id,
                code,
                title,
                title_en,
                type
            FROM skill_nodes
            WHERE (
                title ILIKE :term1
                OR title_en ILIKE :term2
                OR array_to_string(alt_labels, ' ') ILIKE :term3
            )
            ORDER BY
                CASE
                    WHEN LOWER(title) = LOWER(:exact) THEN 1
                    WHEN LOWER(title_en) = LOWER(:exact) THEN 2
                    WHEN title ILIKE :prefix THEN 3
                    WHEN title_en ILIKE :prefix THEN 4
                    ELSE 5
                END,
                title ASC
            LIMIT :limit
        ", [
            'term1' => $searchTerm,
            'term2' => $searchTerm,
            'term3' => $searchTerm,
            'exact' => $query,
            'prefix' => $query . '%',
            'limit' => $limit,
        ]);

        return response()->json([
            'status' => 'success',
            'query' => $query,
            'total' => count($skills),
            'data' => $skills,
        ]);
    }
}
