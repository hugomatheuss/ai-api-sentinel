<?php

use App\Actions\GetCommonIssuesAction;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = app(GetCommonIssuesAction::class);
});

test('returns top 10 most common issues from validation reports', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();

    // Create reports with various issues
    ValidationReport::factory()->for($version)->create([
        'issues' => [
            ['type' => 'missing_operation_id', 'severity' => 'warning'],
            ['type' => 'missing_description', 'severity' => 'warning'],
        ],
        'created_at' => now()->subDays(5),
    ]);

    ValidationReport::factory()->for($version)->create([
        'issues' => [
            ['type' => 'missing_operation_id', 'severity' => 'warning'],
            ['type' => 'missing_operation_id', 'severity' => 'warning'],
        ],
        'created_at' => now()->subDays(3),
    ]);

    ValidationReport::factory()->for($version)->create([
        'issues' => [
            ['type' => 'invalid_schema', 'severity' => 'error'],
        ],
        'created_at' => now()->subDay(),
    ]);

    $result = $this->action->handle(30);

    expect($result)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->first())->toBeGreaterThan(0)
        ->and($result->has('missing_operation_id'))->toBeTrue()
        ->and($result->get('missing_operation_id'))->toBe(3);
});

test('filters issues by date range', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();

    // Old report (outside range)
    ValidationReport::factory()->for($version)->create([
        'issues' => [
            ['type' => 'old_issue', 'severity' => 'warning'],
        ],
        'created_at' => now()->subDays(40),
    ]);

    // Recent report (inside range)
    ValidationReport::factory()->for($version)->create([
        'issues' => [
            ['type' => 'recent_issue', 'severity' => 'warning'],
        ],
        'created_at' => now()->subDays(5),
    ]);

    $result = $this->action->handle(30);

    expect($result->has('recent_issue'))->toBeTrue()
        ->and($result->has('old_issue'))->toBeFalse();
});

test('returns empty collection when no reports exist', function () {
    $result = $this->action->handle(30);

    expect($result)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->isEmpty())->toBeTrue();
});

test('limits results to top 10', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();

    // Create report with 15 different issue types
    $issues = [];
    for ($i = 1; $i <= 15; $i++) {
        $issues[] = ['type' => "issue_type_$i", 'severity' => 'warning'];
    }

    ValidationReport::factory()->for($version)->create([
        'issues' => $issues,
        'created_at' => now(),
    ]);

    $result = $this->action->handle(30);

    expect($result->count())->toBeLessThanOrEqual(10);
});

test('sorts issues by frequency descending', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();

    ValidationReport::factory()->for($version)->create([
        'issues' => [
            ['type' => 'common', 'severity' => 'warning'],
            ['type' => 'common', 'severity' => 'warning'],
            ['type' => 'common', 'severity' => 'warning'],
            ['type' => 'rare', 'severity' => 'warning'],
        ],
        'created_at' => now(),
    ]);

    $result = $this->action->handle(30);

    expect($result->keys()->first())->toBe('common')
        ->and($result->first())->toBe(3);
});
