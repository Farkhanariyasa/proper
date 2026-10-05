<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Anda dinonaktifkan.',
            ], 403);
        }

        // Superadmin memiliki akses bypass ke semua fitur
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        if (! $user->hasPermission($permission)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Anda tidak memiliki izin akses untuk: {$permission}.",
            ], 403);
        }

        return $next($request);
    }
}
