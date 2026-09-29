<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SkillNode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    /**
     * GET /api/v1/nodes/:id
     * Detail lengkap node kategori atau keahlian tanpa mengekspos source_uri
     */
    public function show(string $id): JsonResponse
    {
        if (!is_numeric($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter ID harus berupa angka',
            ], 400);
        }

        $nodeId = (int) $id;

        // Ambil data node
        $node = DB::table('skill_nodes')
            ->where('id', $nodeId)
            ->first([
                'id',
                'code',
                'title',
                'title_en',
                'type',
                'is_layer1',
                'description',
                'description_en',
                'alt_labels',
                'alt_labels_en',
            ]);

        if (!$node) {
            return response()->json([
                'status' => 'error',
                'message' => 'Node taksonomi tidak ditemukan',
            ], 404);
        }

        // Ambil daftar parent (broader)
        $parents = DB::select("
            SELECT
                p.id,
                p.code,
                p.title
            FROM skill_hierarchy h
            JOIN skill_nodes p ON h.parent_id = p.id
            WHERE h.child_id = :node_id
            ORDER BY h.sort_order ASC, p.id ASC
        ", ['node_id' => $nodeId]);

        // Ambil daftar children
        $children = DB::select("
            SELECT
                c.id,
                c.code,
                c.title,
                EXISTS(SELECT 1 FROM skill_hierarchy WHERE parent_id = c.id) AS \"hasChildren\"
            FROM skill_hierarchy h
            JOIN skill_nodes c ON h.child_id = c.id
            WHERE h.parent_id = :node_id
            ORDER BY
                c.code ASC NULLS LAST,
                c.title ASC
        ", ['node_id' => $nodeId]);

        $formattedParents = array_map(function ($p) {
            return [
                'id' => (int) $p->id,
                'code' => $p->code,
                'title' => $p->title,
            ];
        }, $parents);

        $formattedChildren = array_map(function ($c) {
            return [
                'id' => (int) $c->id,
                'code' => $c->code,
                'title' => $c->title,
                'hasChildren' => (bool) $c->hasChildren,
            ];
        }, $children);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (int) $node->id,
                'code' => $node->code,
                'title' => $node->title,
                'titleEn' => $node->title_en,
                'type' => $node->type,
                'isLayer1' => (bool) $node->is_layer1,
                'description' => $node->description,
                'descriptionEn' => $node->description_en,
                'altLabels' => SkillNode::parsePgArray($node->alt_labels),
                'altLabelsEn' => SkillNode::parsePgArray($node->alt_labels_en),
                'parents' => $formattedParents,
                'children' => $formattedChildren,
            ],
        ]);
    }
}
