<?php

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
    // Health check
    Route::get('/health', [ContractValidationController::class, 'health']);

    // Contract validation
    Route::post('/validate', [ContractValidationController::class, 'validate'])
        ->name('api.validate');

    // Version comparison
    Route::post('/compare', [ContractValidationController::class, 'compare'])
        ->name('api.compare');

    // Contract version status
    Route::get('/contracts/{contract}/versions/{version}/status', [ContractValidationController::class, 'status'])
        ->name('api.status');
});
