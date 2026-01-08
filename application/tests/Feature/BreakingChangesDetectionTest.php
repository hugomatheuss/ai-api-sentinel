<?php

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Services\BreakingChangesDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('detects removed endpoints as breaking changes', function () {
    // Create old version with 2 endpoints
    $oldYaml = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
paths:
  /users:
    get:
      summary: Get users
      responses:
        '200':
          description: Success
  /posts:
    get:
      summary: Get posts
      responses:
        '200':
          description: Success
YAML;

    // Create new version with only 1 endpoint (removed /posts)
    $newYaml = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 2.0.0
paths:
  /users:
    get:
      summary: Get users
      responses:
        '200':
          description: Success
YAML;

    // Save files to storage and create versions
    Storage::put('test/v1.yaml', $oldYaml);
    Storage::put('test/v2.yaml', $newYaml);

    $contract = Contract::factory()->create();

    $oldVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '1.0.0',
        'file_path' => 'test/v1.yaml',
    ]);

    $newVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '2.0.0',
        'file_path' => 'test/v2.yaml',
    ]);

    $detector = app(BreakingChangesDetector::class);
    $changes = $detector->detect($oldVersion, $newVersion);

    expect($changes)->not->toBeEmpty();

    $endpointRemovals = collect($changes)->where('type', 'endpoint_removed');
    expect($endpointRemovals)->toHaveCount(1);
    expect($endpointRemovals->first()['severity'])->toBe('critical');
});

test('detects removed HTTP methods as breaking changes', function () {
    // Old version with GET and POST
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
    post:
      responses:
        '201':
          description: Created
YAML;

    // New version with only GET
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

    Storage::put('test/v1.yaml', $oldYaml);
    Storage::put('test/v2.yaml', $newYaml);

    $contract = Contract::factory()->create();

    $oldVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '1.0.0',
        'file_path' => 'test/v1.yaml',
    ]);

    $newVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '2.0.0',
        'file_path' => 'test/v2.yaml',
    ]);

    $detector = app(BreakingChangesDetector::class);
    $changes = $detector->detect($oldVersion, $newVersion);

    $methodRemovals = collect($changes)->where('type', 'method_removed');
    expect($methodRemovals)->toHaveCount(1);
    expect($methodRemovals->first()['method'])->toBe('POST');
    expect($methodRemovals->first()['severity'])->toBe('critical');
});

test('detects new required parameters as breaking changes', function () {
    // Old version without required parameter
    $oldYaml = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
paths:
  /users:
    get:
      parameters:
        - name: page
          in: query
          required: false
          schema:
            type: integer
      responses:
        '200':
          description: Success
YAML;

    // New version with new required parameter
    $newYaml = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 2.0.0
paths:
  /users:
    get:
      parameters:
        - name: page
          in: query
          required: false
          schema:
            type: integer
        - name: apiKey
          in: query
          required: true
          schema:
            type: string
      responses:
        '200':
          description: Success
YAML;

    Storage::put('test/v1.yaml', $oldYaml);
    Storage::put('test/v2.yaml', $newYaml);

    $contract = Contract::factory()->create();

    $oldVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '1.0.0',
        'file_path' => 'test/v1.yaml',
    ]);

    $newVersion = ContractVersion::factory()->create([
        'contract_id' => $contract->id,
        'version' => '2.0.0',
        'file_path' => 'test/v2.yaml',
    ]);

    $detector = app(BreakingChangesDetector::class);
    $changes = $detector->detect($oldVersion, $newVersion);

    $requiredParams = collect($changes)->where('type', 'required_parameter_added');
    expect($requiredParams)->toHaveCount(1);
    expect($requiredParams->first()['parameter'])->toBe('apiKey');
    expect($requiredParams->first()['severity'])->toBe('critical');
});

test('groups breaking changes by category', function () {
    $detector = app(BreakingChangesDetector::class);

    $changes = [
        ['type' => 'endpoint_removed', 'category' => 'endpoints'],
        ['type' => 'parameter_removed', 'category' => 'parameters'],
        ['type' => 'schema_removed', 'category' => 'schemas'],
        ['type' => 'endpoint_removed', 'category' => 'endpoints'],
    ];

    $grouped = $detector->groupByCategory($changes);

    expect($grouped)->toHaveKeys(['endpoints', 'parameters', 'schemas']);
    expect($grouped['endpoints'])->toHaveCount(2);
    expect($grouped['parameters'])->toHaveCount(1);
    expect($grouped['schemas'])->toHaveCount(1);
});

test('counts breaking changes by severity', function () {
    $detector = app(BreakingChangesDetector::class);

    $changes = [
        ['severity' => 'critical'],
        ['severity' => 'critical'],
        ['severity' => 'warning'],
        ['severity' => 'info'],
    ];

    $counts = $detector->countBySeverity($changes);

    expect($counts['critical'])->toBe(2);
    expect($counts['warning'])->toBe(1);
    expect($counts['info'])->toBe(1);
});
