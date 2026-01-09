<?php

use App\Actions\GetBreakingChangesTrendsAction;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->action = app(GetBreakingChangesTrendsAction::class);
});
test('returns breaking changes trends grouped by date', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();
    ValidationReport::factory()->for($version)->create([
        'breaking_changes' => [
            ['type' => 'removed_endpoint', 'severity' => 'critical'],
        ],
        'created_at' => now()->subDays(5)->startOfDay(),
    ]);
    ValidationReport::factory()->for($version)->create([
        'breaking_changes' => [
            ['type' => 'changed_type', 'severity' => 'major'],
        ],
        'created_at' => now()->subDays(3)->startOfDay(),
    ]);
    $result = $this->action->handle(30);
    expect($result)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->count())->toBeGreaterThan(0);
});
test('excludes reports without breaking changes', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();
    ValidationReport::factory()->for($version)->create([
        'breaking_changes' => null,
        'created_at' => now()->subDays(5),
    ]);
    ValidationReport::factory()->for($version)->create([
        'breaking_changes' => [],
        'created_at' => now()->subDays(3),
    ]);
    ValidationReport::factory()->for($version)->create([
        'breaking_changes' => [
            ['type' => 'change', 'severity' => 'critical'],
        ],
        'created_at' => now()->subDay(),
    ]);
    $result = $this->action->handle(30);
    expect($result->count())->toBe(1);
});
test('returns empty collection when no breaking changes exist', function () {
    $result = $this->action->handle(30);
    expect($result)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->isEmpty())->toBeTrue();
});
