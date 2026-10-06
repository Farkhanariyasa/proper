<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class EducationLevelController extends Controller
{
    /**
     * GET /api/education-levels
     */
    public function index(): JsonResponse
    {
        $levels = Cache::remember('master:education_levels', 604800, function () {
            return EducationLevel::orderBy('sort_order', 'asc')->get();
        });

        return response()->json([
            'status' => 'success',
            'data' => $levels,
        ]);
    }
}
