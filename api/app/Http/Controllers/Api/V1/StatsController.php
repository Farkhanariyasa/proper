<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * GET /api/v1/stats
     * Statistik jumlah kategori, keahlian, dan relasi taksonomi
     */
    public function index(): JsonResponse
    {
        $stats = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM skill_nodes WHERE type = 'concept') AS total_categories,
                (SELECT COUNT(*) FROM skill_nodes WHERE type = 'skill') AS total_skills,
                (SELECT COUNT(*) FROM skill_hierarchy) AS total_relations
        ");

        $databaseName = config('database.connections.pgsql.database', 'proper');

        return response()->json([
            'status' => 'success',
            'database' => $databaseName,
            'version' => 'v1.2.1',
            'totalCategories' => (int) ($stats->total_categories ?? 0),
            'totalSkills' => (int) ($stats->total_skills ?? 0),
            'totalRelations' => (int) ($stats->total_relations ?? 0),
        ]);
    }
}
