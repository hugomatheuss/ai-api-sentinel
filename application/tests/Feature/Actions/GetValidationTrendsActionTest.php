<?php

use App\Actions\GetValidationTrendsAction;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = app(GetValidationTrendsAction::class);
});

test('returns validation trends grouped by date', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();

    // Create reports on different days
    ValidationReport::factory()->for($version)->create([
        'status' => 'passed',
        'created_at' => now()->subDays(5)->startOfDay(),
    ]);

    ValidationReport::factory()->for($version)->create([
        'status' => 'failed',
        'created_at' => now()->subDays(5)->startOfDay()->addHour(),
    ]);

    ValidationReport::factory()->for($version)->create([
        'status' => 'passed',
        'created_at' => now()->subDays(3)->startOfDay(),
    ]);

    $result = $this->action->handle(30);

    expect($result)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->count())->toBeGreaterThan(0);
});

test('counts passed and failed validations separately', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();

    $today = now()->startOfDay();

    ValidationReport::factory()->for($version)->count(3)->create([
        'status' => 'passed',
        'created_at' => $today,
    ]);

    ValidationReport::factory()->for($version)->count(2)->create([
        'status' => 'failed',
        'created_at' => $today,
    ]);

    $result = $this->action->handle(1);

    expect($result->first())
        ->toHaveKey('total')
        ->toHaveKey('passed')
        ->toHaveKey('failed')
        ->and($result->first()->total)->toBe(5)
        ->and($result->first()->passed)->toBe(3)
        ->and($result->first()->failed)->toBe(2);
});

test('filters by date range', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();

    // Old report (outside range)
    ValidationReport::factory()->for($version)->create([
        'status' => 'passed',
        'created_at' => now()->subDays(40),
    ]);

    // Recent reports (inside range)
    ValidationReport::factory()->for($version)->count(3)->create([
        'status' => 'passed',
        'created_at' => now()->subDays(5),
    ]);

    $result = $this->action->handle(30);

    // Should only include recent reports
    expect($result->sum('total'))->toBe(3);
});

test('returns empty collection when no reports exist', function () {
    $result = $this->action->handle(30);

    expect($result)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->isEmpty())->toBeTrue();
});

test('orders results by date ascending', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();

    ValidationReport::factory()->for($version)->create([
        'status' => 'passed',
        'created_at' => now()->subDays(5)->startOfDay(),
    ]);

    ValidationReport::factory()->for($version)->create([
        'status' => 'passed',
        'created_at' => now()->subDays(10)->startOfDay(),
    ]);

    ValidationReport::factory()->for($version)->create([
        'status' => 'passed',
        'created_at' => now()->subDays(2)->startOfDay(),
    ]);

    $result = $this->action->handle(30);

    $dates = $result->pluck('date')->toArray();
    $sortedDates = $dates;
    sort($sortedDates);

    expect($dates)->toBe($sortedDates);
});
