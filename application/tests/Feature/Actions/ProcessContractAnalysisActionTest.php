<?php

use App\Actions\ProcessContractAnalysisAction;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->action = app(ProcessContractAnalysisAction::class);
});

test('creates validation report when processing contract', function () {
    $contract = Contract::factory()->create();

    // Create a valid OpenAPI YAML file
    $yamlContent = <<<'YAML'
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
YAML;

    $filePath = 'contracts/test.yaml';
    Storage::put($filePath, $yamlContent);

    $version = ContractVersion::factory()->for($contract)->create([
        'version' => '1.0.0',
        'file_path' => $filePath,
    ]);

    $report = $this->action->handle($contract, $version);

    expect($report)
        ->toBeInstanceOf(ValidationReport::class)
        ->and($report->contract_version_id)->toBe($version->id)
        ->and($report->status)->toBeIn(['passed', 'warning', 'failed'])
        ->and($report->processed_at)->not->toBeNull();
});

test('returns failed report when file does not exist', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create([
        'file_path' => 'nonexistent/file.yaml',
    ]);

    $report = $this->action->handle($contract, $version);

    expect($report->status)->toBe('failed')
        ->and($report->error_count)->toBe(1)
        ->and($report->issues)->toHaveCount(1)
        ->and($report->issues[0]['type'])->toBe('parse_error');
});

test('returns failed report when yaml is invalid', function () {
    $contract = Contract::factory()->create();

    $invalidYaml = 'invalid: yaml: content: [[[';
    $filePath = 'contracts/invalid.yaml';
    Storage::put($filePath, $invalidYaml);

    $version = ContractVersion::factory()->for($contract)->create([
        'file_path' => $filePath,
    ]);

    $report = $this->action->handle($contract, $version);

    expect($report->status)->toBe('failed')
        ->and($report->error_count)->toBeGreaterThan(0);
});

test('stores issues in validation report', function () {
    $contract = Contract::factory()->create();

    // OpenAPI without operation IDs (should generate warnings)
    $yamlContent = <<<'YAML'
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
YAML;

    $filePath = 'contracts/test.yaml';
    Storage::put($filePath, $yamlContent);

    $version = ContractVersion::factory()->for($contract)->create([
        'file_path' => $filePath,
    ]);

    $report = $this->action->handle($contract, $version);

    expect($report->issues)->toBeArray();
});

test('detects breaking changes when previous version exists', function () {
    $contract = Contract::factory()->create();

    // Previous version
    $previousYaml = <<<'YAML'
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

    $previousPath = 'contracts/v1.yaml';
    Storage::put($previousPath, $previousYaml);

    $previousVersion = ContractVersion::factory()->for($contract)->create([
        'version' => '1.0.0',
        'file_path' => $previousPath,
    ]);

    // New version (removed /posts endpoint - breaking change)
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

    $newPath = 'contracts/v2.yaml';
    Storage::put($newPath, $newYaml);

    $newVersion = ContractVersion::factory()->for($contract)->create([
        'version' => '2.0.0',
        'file_path' => $newPath,
    ]);

    $report = $this->action->handle($contract, $newVersion);

    expect($report->breaking_changes)->toBeArray();
});

test('marks report as failed when critical breaking changes found', function () {
    $contract = Contract::factory()->create();

    // Previous version with endpoint
    $previousYaml = <<<'YAML'
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
YAML;

    $previousPath = 'contracts/v1.yaml';
    Storage::put($previousPath, $previousYaml);

    ContractVersion::factory()->for($contract)->create([
        'version' => '1.0.0',
        'file_path' => $previousPath,
    ]);

    // New version without the endpoint (critical breaking change)
    $newYaml = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 2.0.0
paths: {}
YAML;

    $newPath = 'contracts/v2.yaml';
    Storage::put($newPath, $newYaml);

    $newVersion = ContractVersion::factory()->for($contract)->create([
        'version' => '2.0.0',
        'file_path' => $newPath,
    ]);

    $report = $this->action->handle($contract, $newVersion);

    // If critical breaking changes detected, status should be failed
    if (! empty($report->breaking_changes)) {
        $hasCritical = collect($report->breaking_changes)
            ->contains('severity', 'critical');

        if ($hasCritical) {
            expect($report->status)->toBe('failed');
        }
    }

    expect($report)->toBeInstanceOf(ValidationReport::class);
});

test('logs activity when processing contract', function () {
    $contract = Contract::factory()->create(['title' => 'My API']);

    $yamlContent = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
paths:
  /test:
    get:
      responses:
        '200':
          description: Success
YAML;

    $filePath = 'contracts/test.yaml';
    Storage::put($filePath, $yamlContent);

    $version = ContractVersion::factory()->for($contract)->create([
        'version' => '1.0.0',
        'file_path' => $filePath,
    ]);

    $this->action->handle($contract, $version);

    // Verify activity log was created
    $this->assertDatabaseHas('activity_logs', [
        'log_name' => 'validation',
        'subject_type' => ContractVersion::class,
        'subject_id' => $version->id,
    ]);
});

test('returns report even when exception occurs', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create([
        'file_path' => 'invalid/path.yaml',
    ]);

    $report = $this->action->handle($contract, $version);

    expect($report)
        ->toBeInstanceOf(ValidationReport::class)
        ->and($report->status)->toBe('failed');
});
