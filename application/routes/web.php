<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ContractAnalysisController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractVersionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
