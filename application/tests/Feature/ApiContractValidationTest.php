<?php

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('health endpoint returns ok status', function () {
    $response = getJson('/api/v1/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'service' => 'API Sentinel',
        ]);
});

test('validates OpenAPI contract via API', function () {
    $yaml = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
  description: A test API
paths:
  /users:
    get:
      summary: Get users
      responses:
        '200':
          description: Success
YAML;

    $file = UploadedFile::fake()->createWithContent('test.yaml', $yaml);

    $response = postJson('/api/v1/validate', [
        'file' => $file,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'status',
            'metadata',
            'validation' => [
                'status',
                'error_count',
                'warning_count',
                'info_count',
                'issues',
            ],
            'endpoints' => [
                'count',
                'list',
            ],
        ]);

    expect($response->json('success'))->toBeTrue();
    expect($response->json('endpoints.count'))->toBeGreaterThan(0);
});

test('validation API rejects invalid file types', function () {
    $file = UploadedFile::fake()->create('test.txt', 100);

    $response = postJson('/api/v1/validate', [
        'file' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('validation API detects OpenAPI issues', function () {
    // Invalid OpenAPI - missing required fields
    $yaml = <<<'YAML'
openapi: 3.0.0
paths:
  /users:
    get:
      responses:
        '200':
          description: Success
YAML;

    $file = UploadedFile::fake()->createWithContent('invalid.yaml', $yaml);

    $response = postJson('/api/v1/validate', [
        'file' => $file,
    ]);

    // Should return 422 because of validation errors
    $response->assertStatus(422);
    expect($response->json('validation.error_count'))->toBeGreaterThan(0);
});

test('compare API detects breaking changes', function () {
    $contract = Contract::factory()->create();

    // Create old version
    $oldYaml = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
paths:
  /users:
    get:
      responses:
        '200':
          description: Success
  /posts:
    get:
      responses:
        '200':
          description: Success
YAML;

    Storage::put('test/old.yaml', $oldYaml);
    $oldVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '1.0.0',
        'file_path' => 'test/old.yaml',
    ]);

    // Create new version with removed endpoint
    $newYaml = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 2.0.0
paths:
  /users:
    get:
      responses:
        '200':
          description: Success
YAML;

    Storage::put('test/new.yaml', $newYaml);
    $newVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '2.0.0',
        'file_path' => 'test/new.yaml',
    ]);

    $response = postJson('/api/v1/compare', [
        'old_version_id' => $oldVersion->id,
        'new_version_id' => $newVersion->id,
    ]);

    // Should return successfully with comparison results
    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'comparison' => [
                'old_version',
                'new_version',
                'has_breaking_changes',
                'has_blocking_changes',
            ],
            'breaking_changes' => [
                'total',
                'critical',
                'warning',
                'info',
            ],
            'recommendation',
        ]);
});

test('status API returns validation report', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
    ]);

    $report = ValidationReport::factory()->create([
        'contract_version_id' => $version->id,
        'status' => 'passed',
        'error_count' => 0,
        'warning_count' => 2,
    ]);

    $response = getJson("/api/v1/contracts/{$contract->id}/versions/{$version->id}/status");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'validation' => [
                'status' => 'passed',
                'error_count' => 0,
                'warning_count' => 2,
            ],
        ]);
});

test('status API returns 404 when no validation report exists', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
    ]);

    $response = getJson("/api/v1/contracts/{$contract->id}/versions/{$version->id}/status");

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'No validation report found. Please run analysis first.',
        ]);
});

test('compare API validates required fields', function () {
    $response = postJson('/api/v1/compare', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['old_version_id', 'new_version_id']);
});
