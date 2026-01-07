<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ContractController;
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

