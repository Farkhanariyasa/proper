<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /api/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['roles:id,name,display_name'])
            ->latest('id');

        // Pencarian berdasarkan nama, username, atau email
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filter berdasarkan role
        if ($request->filled('role')) {
            $roleName = $request->query('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        // Filter berdasarkan status aktif
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = (int) $request->query('per_page', 15);
        $users = $perPage === -1 ? $query->get() : $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $users,
        ]);
    }

    /**
     * POST /api/admin/users
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'username'  => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email'     => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'role_ids'  => ['required', 'array', 'min:1'],
            'role_ids.*'=> ['exists:roles,id'],
        ]);

        $user = User::create([
            'name'       => $validated['name'],
            'username'   => strtolower($validated['username']),
            'email'      => $validated['email'] ?? null,
            'password'   => Hash::make($validated['password']),
            'is_active'  => $validated['is_active'] ?? true,
            'created_by' => $request->user()?->id,
        ]);

        $user->roles()->sync($validated['role_ids']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengguna berhasil ditambahkan.',
            'data'    => $user->load('roles:id,name,display_name'),
        ], 201);
    }

    /**
     * GET /api/admin/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles:id,name,display_name'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $user,
        ]);
    }

    /**
     * PUT/PATCH /api/admin/users/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:100'],
            'username'  => ['sometimes', 'required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email'     => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password'  => ['nullable', 'string', 'min:6'],
            'is_active' => ['sometimes', 'boolean'],
            'role_ids'  => ['sometimes', 'array', 'min:1'],
            'role_ids.*'=> ['exists:roles,id'],
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['username'])) {
            $user->username = strtolower($validated['username']);
        }
        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        if (isset($validated['is_active'])) {
            // Hindari menonaktifkan diri sendiri
            if ($user->id === $request->user()?->id && ! $validated['is_active']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Anda tidak dapat menonaktifkan akun sendiri.',
                ], 422);
            }
            $user->is_active = $validated['is_active'];
        }

        $user->save();

        if (isset($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Data pengguna berhasil diperbarui.',
            'data'    => $user->load('roles:id,name,display_name'),
        ]);
    }

    /**
     * DELETE /api/admin/users/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Pencegahan: jangan hapus akun sendiri
        if ($user->id === $request->user()?->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/admin/users/{id}/toggle-active
     */
    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()?->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak dapat mengubah status akun Anda sendiri.',
            ], 422);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Status aktif pengguna berhasil diubah.',
            'data'    => [
                'id'        => $user->id,
                'is_active' => $user->is_active,
            ],
        ]);
    }
}
