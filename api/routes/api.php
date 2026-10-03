<?php

use App\Http\Controllers\Api\EducationLevelController;
use App\Http\Controllers\Api\EscoSkillController;
use App\Http\Controllers\Api\JobSeekerController;
use App\Http\Controllers\Api\LowonganController;
use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\WilayahController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\KbjiController;
use App\Http\Controllers\Api\V1\NodeController;
use App\Http\Controllers\Api\V1\SkillSearchController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Tasks API resource
Route::apiResource('tasks', TaskController::class);

// Master Wilayah (Sumber Kepmendagri cahyadsn/wilayah)
Route::get('provinces', [WilayahController::class, 'provinces']);
Route::get('regencies', [WilayahController::class, 'regencies']);

// Master Pendukung (Pendidikan & Keahlian ESCO)
Route::get('education-levels', [EducationLevelController::class, 'index']);
Route::get('esco-skills', [EscoSkillController::class, 'search']);

// Profil Pencari Kerja & Skill
Route::get('job-seekers/options', [JobSeekerController::class, 'options']);
Route::apiResource('job-seekers', JobSeekerController::class)->only(['index', 'store', 'show']);

// Modul Lowongan Kerja
Route::get('lowongan/options', [LowonganController::class, 'options']);
Route::get('lowongan', [LowonganController::class, 'index']);
Route::get('lowongan/{id}', [LowonganController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('lowongan', [LowonganController::class, 'store'])->middleware('permission:lowongan.create');
    Route::put('lowongan/{id}', [LowonganController::class, 'update'])->middleware('permission:lowongan.edit');
    Route::delete('lowongan/{id}', [LowonganController::class, 'destroy'])->middleware('permission:lowongan.delete');
    Route::patch('lowongan/{id}/status', [LowonganController::class, 'toggleStatus'])->middleware('permission:lowongan.edit');
});

// Mesin Penjodohan & Rekomendasi (Smart Matching Engine)
Route::prefix('rekomendasi')->group(function () {
    Route::get('jobs-for-seeker/{jobSeekerId}', [MatchingController::class, 'jobsForSeeker']);
    Route::get('candidates-for-job/{lowonganId}', [MatchingController::class, 'candidatesForJob']);
    Route::get('analysis', [MatchingController::class, 'pairAnalysis']);
});

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

// Autentikasi (Sanctum)
Route::prefix('auth')->group(function () {
    Route::post('login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);
        Route::get('me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'me']);
    });
});

// Admin Manajemen Pengguna, Role, dan Hak Akses
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // Users Management
    Route::get('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index'])->middleware('permission:users.view');
    Route::post('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'store'])->middleware('permission:users.create');
    Route::get('users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show'])->middleware('permission:users.view');
    Route::put('users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update'])->middleware('permission:users.edit');
    Route::delete('users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy'])->middleware('permission:users.delete');
    Route::post('users/{id}/toggle-active', [\App\Http\Controllers\Api\Admin\UserController::class, 'toggleActive'])->middleware('permission:users.edit');

    // Roles Management
    Route::get('roles', [\App\Http\Controllers\Api\Admin\RoleController::class, 'index'])->middleware('permission:roles.view');
    Route::post('roles', [\App\Http\Controllers\Api\Admin\RoleController::class, 'store'])->middleware('permission:roles.create');
    Route::get('roles/{id}', [\App\Http\Controllers\Api\Admin\RoleController::class, 'show'])->middleware('permission:roles.view');
    Route::put('roles/{id}', [\App\Http\Controllers\Api\Admin\RoleController::class, 'update'])->middleware('permission:roles.edit');
    Route::delete('roles/{id}', [\App\Http\Controllers\Api\Admin\RoleController::class, 'destroy'])->middleware('permission:roles.delete');
    Route::put('roles/{id}/permissions', [\App\Http\Controllers\Api\Admin\RoleController::class, 'syncPermissions'])->middleware('permission:permissions.assign');

    // Permissions List
    Route::get('permissions', [\App\Http\Controllers\Api\Admin\PermissionController::class, 'index'])->middleware('permission:roles.view');
});

