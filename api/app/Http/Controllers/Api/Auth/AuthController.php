<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Fleksibel: dukung login lewat username atau email
        $user = User::with('roles.permissions')
            ->where(function ($query) use ($validated) {
                $query->where('username', $validated['username'])
                      ->orWhere('email', $validated['username']);
            })
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Kombinasi kredensial (username/password) tidak cocok.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Superadmin.',
            ], 403);
        }

        // Catat waktu login
        $user->forceFill(['last_login_at' => now()])->save();

        // Buat access token dengan Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil.',
            'data'    => [
                'token' => $token,
                'user'  => [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'username'    => $user->username,
                    'email'       => $user->email,
                    'is_active'   => $user->is_active,
                    'roles'       => $user->roles->pluck('name'),
                    'role_names'  => $user->roles->pluck('display_name'),
                    'permissions' => $user->getAllPermissions(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil logout.',
        ]);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'          => $user->id,
                'name'        => $user->name,
                'username'    => $user->username,
                'email'       => $user->email,
                'is_active'   => $user->is_active,
                'roles'       => $user->roles->pluck('name'),
                'role_names'  => $user->roles->pluck('display_name'),
                'permissions' => $user->getAllPermissions(),
            ],
        ]);
    }
}
