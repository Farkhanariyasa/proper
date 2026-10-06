<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WilayahController extends Controller
{
    private const CACHE_TTL = 604800; // 7 hari

    /**
     * GET /api/provinces
     * Mengambil daftar seluruh provinsi (dengan cache 7 hari)
     */
    public function provinces(): JsonResponse
    {
        $provinces = Cache::remember('master:provinces', self::CACHE_TTL, function () {
            return Province::orderBy('id', 'asc')->get()->toArray();
        });

        return response()->json([
            'status' => 'success',
            'data' => $provinces,
        ]);
    }

    /**
     * GET /api/regencies?province_id={id}
     * Mengambil daftar kab/kota berdasarkan provinsi terpilih (dengan cache 7 hari)
     */
    public function regencies(Request $request): JsonResponse
    {
        $provinceId = $request->query('province_id');
        $cacheKey = 'master:regencies:' . ($provinceId ?: 'all');

        $regencies = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($provinceId) {
            $query = Regency::query();

            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }

            return $query->orderBy('id', 'asc')->get()->toArray();
        });

        return response()->json([
            'status' => 'success',
            'data' => $regencies,
        ]);
    }
}
