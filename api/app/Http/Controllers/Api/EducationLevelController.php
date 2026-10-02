<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use Illuminate\Http\JsonResponse;

class EducationLevelController extends Controller
{
    /**
     * GET /api/education-levels
     */
    public function index(): JsonResponse
    {
        $levels = EducationLevel::orderBy('sort_order', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $levels,
        ]);
    }
}
