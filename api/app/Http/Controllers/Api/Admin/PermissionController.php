<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    /**
     * GET /api/admin/permissions
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('group', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $grouped = $permissions->groupBy('group');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'list'    => $permissions,
                'grouped' => $grouped,
            ],
        ]);
    }
}
