<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group.
|
| Authentication: Laravel Sanctum (token-based)
| Base URL: /api/v1/
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ── Health check ─────────────────────────────────────────────────────────
    Route::get('/health', fn () => response()->json([
        'status'  => 'ok',
        'service' => 'Amazon Listing Builder API',
        'version' => '1.0',
        'time'    => now()->toIso8601String(),
    ]))->name('health');

    // ── Auth endpoints ───────────────────────────────────────────────────────
    Route::post('/auth/login',    [\App\Http\Controllers\Api\AuthApiController::class, 'login'])->name('auth.login');
    Route::post('/auth/register', [\App\Http\Controllers\Api\AuthApiController::class, 'register'])->name('auth.register');

    // ── Authenticated API ────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthApiController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me',      [\App\Http\Controllers\Api\AuthApiController::class, 'me'])->name('auth.me');

        // Listings
        Route::get('/listings',            [\App\Http\Controllers\Api\ListingApiController::class, 'index'])->name('listings.index');
        Route::post('/listings',           [\App\Http\Controllers\Api\ListingApiController::class, 'store'])->name('listings.store');
        Route::get('/listings/{import}',   [\App\Http\Controllers\Api\ListingApiController::class, 'show'])->name('listings.show');
        Route::delete('/listings/{import}',[\App\Http\Controllers\Api\ListingApiController::class, 'destroy'])->name('listings.destroy');

        // Generations
        Route::post('/listings/{import}/generate', [\App\Http\Controllers\Api\ListingApiController::class, 'generate'])->name('listings.generate');
        Route::get('/generations',                 [\App\Http\Controllers\Api\ListingApiController::class, 'generations'])->name('generations.index');
        Route::get('/generations/{generation}',    [\App\Http\Controllers\Api\ListingApiController::class, 'generation'])->name('generations.show');

        // Export
        Route::post('/generations/{generation}/export', [\App\Http\Controllers\Api\ListingApiController::class, 'export'])->name('generations.export');

        // Plans
        Route::get('/plans', fn () => response()->json([
            'data' => \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get()
        ]))->name('plans.index');
    });
});

// Fallback
Route::fallback(fn () => response()->json(['message' => 'API endpoint not found.', 'status' => 404], 404));
