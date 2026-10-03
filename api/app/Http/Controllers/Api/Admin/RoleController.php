<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * GET /api/admin/roles
     */
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')
            ->with(['permissions:id,name,display_name,group'])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $roles,
        ]);
    }

    /**
     * POST /api/admin/roles
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:50', 'alpha_dash', 'unique:roles,name'],
            'display_name'   => ['required', 'string', 'max:100'],
            'description'    => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name'         => strtolower($validated['name']),
            'display_name' => $validated['display_name'],
            'description'  => $validated['description'] ?? null,
        ]);

        if (! empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Role berhasil dibuat.',
            'data'    => $role->load('permissions:id,name,display_name,group'),
        ], 201);
    }

    /**
     * GET /api/admin/roles/{id}
     */
    public function show(int $id): JsonResponse
    {
        $role = Role::with(['permissions:id,name,display_name,group'])->withCount('users')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $role,
        ]);
    }

    /**
     * PUT/PATCH /api/admin/roles/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'display_name'   => ['sometimes', 'required', 'string', 'max:100'],
            'description'    => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);

        if (isset($validated['display_name'])) {
            $role->display_name = $validated['display_name'];
        }
        if (array_key_exists('description', $validated)) {
            $role->description = $validated['description'];
        }
        $role->save();

        if (isset($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Role berhasil diperbarui.',
            'data'    => $role->load('permissions:id,name,display_name,group'),
        ]);
    }

    /**
     * DELETE /api/admin/roles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        // Jangan hapus role core bawaan
        if (in_array($role->name, ['superadmin', 'operator', 'pimpinan'], true)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Role standar '{$role->name}' tidak boleh dihapus.",
            ], 422);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak dapat menghapus role yang masih memiliki pengguna terdaftar.',
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Role berhasil dihapus.',
        ]);
    }

    /**
     * PUT /api/admin/roles/{id}/permissions
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission_ids'   => ['present', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        return response()->json([
            'status'  => 'success',
            'message' => "Permission untuk role '{$role->display_name}' berhasil diperbarui.",
            'data'    => $role->load('permissions:id,name,display_name,group'),
        ]);
    }
}
