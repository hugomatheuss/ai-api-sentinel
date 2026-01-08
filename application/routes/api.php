<?php

use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\Api\ContractValidationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API v1
Route::prefix('v1')->group(function () {
    // Public endpoints (no authentication required)
    Route::get('/health', [ContractValidationController::class, 'health']);

    // Token management (no auth required for creating first token)
    Route::post('/tokens', [ApiTokenController::class, 'store'])
        ->name('api.tokens.store');

    // Protected endpoints (require API token)
    // NOTE: Authentication is optional by default for MVP
    // Uncomment middleware to require authentication:
    // ->middleware('auth.api.token')

    Route::middleware(['throttle:api'])->group(function () {
        // Contract validation
        Route::post('/validate', [ContractValidationController::class, 'validate'])
            ->name('api.validate');

        // Version comparison
        Route::post('/compare', [ContractValidationController::class, 'compare'])
            ->name('api.compare');

        // Contract version status
        Route::get('/contracts/{contract}/versions/{version}/status', [ContractValidationController::class, 'status'])
            ->name('api.status');

        // Token management (authenticated)
        Route::get('/tokens', [ApiTokenController::class, 'index'])
            ->name('api.tokens.index');

        Route::delete('/tokens/{id}', [ApiTokenController::class, 'destroy'])
            ->name('api.tokens.destroy');
    });
});
