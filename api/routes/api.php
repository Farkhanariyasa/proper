<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\KbjiController;
use App\Http\Controllers\Api\V1\NodeController;
use App\Http\Controllers\Api\V1\SkillSearchController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Tasks API resource
Route::apiResource('tasks', TaskController::class);

// REST API Mandiri Taksonomi Keahlian (v1)
Route::prefix('v1')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{id}/children', [CategoryController::class, 'children']);
    Route::get('nodes/{id}', [NodeController::class, 'show']);
    Route::get('skills/search', [SkillSearchController::class, 'search']);
    Route::get('stats', [StatsController::class, 'index']);

    // Klasifikasi Baku Jabatan Indonesia (KBJI)
    Route::get('kbji', [KbjiController::class, 'index']);
    Route::get('kbji/search', [KbjiController::class, 'search']);
    Route::get('kbji/stats', [KbjiController::class, 'stats']);
    Route::get('kbji/{code}/children', [KbjiController::class, 'children']);
    Route::get('kbji/{code}', [KbjiController::class, 'show']);
});
