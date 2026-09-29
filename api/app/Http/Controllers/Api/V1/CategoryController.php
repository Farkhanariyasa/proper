<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * GET /api/v1/categories
     * Mengambil 4 pilar utama (is_layer1 = TRUE), urutan K -> L -> S -> T
     */
    public function index(): JsonResponse
    {
        $categories = DB::select("
            SELECT
                id,
                code,
                title,
                title_en AS \"titleEn\",
                type,
                is_layer1 AS \"isLayer1\",
                EXISTS(SELECT 1 FROM skill_hierarchy WHERE parent_id = skill_nodes.id) AS \"hasChildren\"
            FROM skill_nodes
            WHERE is_layer1 = TRUE
            ORDER BY CASE code
                WHEN 'K' THEN 1
                WHEN 'L' THEN 2
                WHEN 'S' THEN 3
                WHEN 'T' THEN 4
                ELSE 5
            END
        ");

        $data = array_map(function ($cat) {
            return [
                'id' => (int) $cat->id,
                'code' => $cat->code,
                'title' => $cat->title,
                'titleEn' => $cat->titleEn,
                'type' => $cat->type,
                'isLayer1' => (bool) $cat->isLayer1,
                'hasChildren' => (bool) $cat->hasChildren,
            ];
        }, $categories);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/v1/categories/:id/children
     * Drill-down anak kategori atau skill berdasarkan ID integer atau code string
     */
    public function children(string $id): JsonResponse
    {
        // 1. Resolve parent (bisa numeric id atau code string seperti 'K', '00')
        $parent = is_numeric($id)
            ? DB::table('skill_nodes')->where('id', (int) $id)->first(['id', 'code', 'title', 'title_en'])
            : DB::table('skill_nodes')->where('code', $id)->first(['id', 'code', 'title', 'title_en']);

        if (!$parent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori atau node tidak ditemukan',
            ], 404);
        }

        // 2. Query anak langsung dan cek apakah punya turunan lagi (hasChildren)
        $children = DB::select("
            SELECT
                n.id,
                n.code,
                n.title,
                n.title_en AS \"titleEn\",
                n.type,
                EXISTS(SELECT 1 FROM skill_hierarchy WHERE parent_id = n.id) AS \"hasChildren\"
            FROM skill_hierarchy h
            JOIN skill_nodes n ON h.child_id = n.id
            WHERE h.parent_id = :parent_id
            ORDER BY
                n.code ASC NULLS LAST,
                n.title ASC
        ", ['parent_id' => $parent->id]);

        $formattedChildren = array_map(function ($child) {
            return [
                'id' => (int) $child->id,
                'code' => $child->code,
                'title' => $child->title,
                'titleEn' => $child->titleEn,
                'type' => $child->type,
                'hasChildren' => (bool) $child->hasChildren,
            ];
        }, $children);

        return response()->json([
            'status' => 'success',
            'parent' => [
                'id' => (int) $parent->id,
                'code' => $parent->code,
                'title' => $parent->title,
                'titleEn' => $parent->title_en,
            ],
            'totalChildren' => count($formattedChildren),
            'children' => $formattedChildren,
        ]);
    }
}
