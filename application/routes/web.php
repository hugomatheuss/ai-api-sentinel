<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ContractAnalysisController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractDiffController;
use App\Http\Controllers\ContractVersionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Health endpoint para monitoring
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// Rotas de APIs
Route::resource('apis', ApiController::class);

// Rotas de Contracts (nested sob APIs)
Route::resource('apis.contracts', ContractController::class)->shallow();

// Rotas de Contract Versions
Route::get('contracts/{contract}/versions/create', [ContractVersionController::class, 'create'])
    ->name('contract-versions.create');
Route::post('contracts/{contract}/versions', [ContractVersionController::class, 'store'])
    ->name('contract-versions.store');
Route::get('contract-versions/{contractVersion}', [ContractVersionController::class, 'show'])
    ->name('contract-versions.show');
Route::get('contract-versions/{contractVersion}/download', [ContractVersionController::class, 'download'])
    ->name('contract-versions.download');
Route::get('/contracts/{contract}/versions/{version}/analyze', [ContractAnalysisController::class, 'show'])
    ->name('contracts.versions.analyze');
Route::post('/contracts/{contract}/versions/{version}/process', [ContractAnalysisController::class, 'process'])
    ->name('contracts.versions.process');
Route::get('/contracts/{contract}/versions/{version}/report', [ContractAnalysisController::class, 'report'])
    ->name('contracts.versions.report');

// Diff comparison
Route::get('/contracts/{contract}/diff/{oldVersion}/{newVersion}', [ContractDiffController::class, 'compare'])
    ->name('contracts.diff');

// Activity logs
Route::get('/logs', [ActivityLogController::class, 'index'])
    ->name('logs.index');
Route::get('/logs/{log}', [ActivityLogController::class, 'show'])
    ->name('logs.show');

// Metrics
Route::get('/metrics', [MetricsController::class, 'index'])
    ->name('metrics.index');

