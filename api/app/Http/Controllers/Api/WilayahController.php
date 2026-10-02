<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    /**
     * GET /api/provinces
     * Mengambil daftar seluruh provinsi
     */
    public function provinces(): JsonResponse
    {
        $provinces = Province::orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $provinces,
        ]);
    }

    /**
     * GET /api/regencies?province_id={id}
     * Mengambil daftar kab/kota berdasarkan provinsi terpilih (urut by ID)
     */
    public function regencies(Request $request): JsonResponse
    {
        $query = Regency::query();

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->query('province_id'));
        }

        $regencies = $query->orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $regencies,
        ]);
    }
}
