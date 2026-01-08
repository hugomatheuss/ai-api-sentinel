<?php

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\Endpoint;
use App\Services\AIAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('calculates quality score based on contract completeness', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'metadata' => [
            'title' => 'Test API',
            'description' => 'A well-documented API',
            'version' => '1.0.0',
        ],
    ]);

    // Create endpoints with summaries
    Endpoint::factory()->count(3)->create([
        'contract_version_id' => $version->id,
        'summary' => 'Test endpoint',
    ]);

    $aiService = app(AIAnalysisService::class);
    $result = $aiService->calculateQualityScore($version);

    expect($result)->toHaveKeys(['score', 'grade', 'deductions']);
    expect($result['score'])->toBeGreaterThan(0);
    expect($result['score'])->toBeLessThanOrEqual(100);
    expect($result['grade'])->toBeIn(['A', 'B', 'C', 'D', 'F']);
});

test('detects naming inconsistencies in endpoints', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
    ]);

    // Create endpoint with snake_case (not recommended)
    Endpoint::factory()->create([
        'contract_version_id' => $version->id,
        'path' => '/api/user_profiles',
        'method' => 'GET',
    ]);

    $aiService = app(AIAnalysisService::class);
    $issues = $aiService->analyzeNaming($version);

    expect($issues)->not->toBeEmpty();
    expect($issues[0]['type'])->toBe('naming_style');
    expect($issues[0]['category'])->toBe('naming');
});

test('detects REST design patterns', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
    ]);

    // Create CRUD endpoints
    Endpoint::factory()->create([
        'contract_version_id' => $version->id,
        'path' => '/users',
        'method' => 'GET',
    ]);

    Endpoint::factory()->create([
        'contract_version_id' => $version->id,
        'path' => '/users',
        'method' => 'POST',
    ]);

    Endpoint::factory()->create([
        'contract_version_id' => $version->id,
        'path' => '/users/{id}',
        'method' => 'PUT',
    ]);

    Endpoint::factory()->create([
        'contract_version_id' => $version->id,
        'path' => '/users/{id}',
        'method' => 'DELETE',
    ]);

    $aiService = app(AIAnalysisService::class);
    $patterns = $aiService->detectDesignPatterns($version);

    expect($patterns)->not->toBeEmpty();
    expect($patterns[0]['pattern'])->toBe('RESTful CRUD');
    expect($patterns[0]['quality'])->toBe('good');
});

test('generates changelog between versions', function () {
    $contract = Contract::factory()->create();

    $oldVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '1.0.0',
    ]);

    Endpoint::factory()->count(5)->create([
        'contract_version_id' => $oldVersion->id,
    ]);

    $newVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '2.0.0',
    ]);

    Endpoint::factory()->count(7)->create([
        'contract_version_id' => $newVersion->id,
    ]);

    $aiService = app(AIAnalysisService::class);
    $changelog = $aiService->generateChangelog($oldVersion, $newVersion);

    expect($changelog)->toContain('Version 2.0.0');
    expect($changelog)->toContain('Added');
});

test('penalizes missing descriptions in quality score', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'metadata' => [
            'title' => 'Test API',
            // No description
        ],
    ]);

    $aiService = app(AIAnalysisService::class);
    $result = $aiService->calculateQualityScore($version);

    expect($result['deductions'])->toContain('Missing API description (-10)');
});

test('penalizes endpoints without summary', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'metadata' => ['description' => 'Test'],
    ]);

    // Create endpoints without summaries
    Endpoint::factory()->count(3)->create([
        'contract_version_id' => $version->id,
        'summary' => null,
    ]);

    $aiService = app(AIAnalysisService::class);
    $result = $aiService->calculateQualityScore($version);

    expect($result['score'])->toBeLessThan(100);
    $hasEndpointPenalty = collect($result['deductions'])->contains(function ($deduction) {
        return str_contains($deduction, 'endpoints without summary');
    });
    expect($hasEndpointPenalty)->toBeTrue();
});
