<?php

use App\Models\Api;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('dashboard page loads successfully', function () {
    get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard.index');
});

test('dashboard displays total counts', function () {
    // Create test data
    $api = Api::factory()->create();
    $contract = Contract::factory()->for($api)->create();
    $version = ContractVersion::factory()->for($contract)->create();
    ValidationReport::factory()->for($version, 'contractVersion')->create();

    get(route('dashboard'))
        ->assertOk()
        ->assertSee('Total APIs')
        ->assertSee('Contracts')
        ->assertSee('Validations');
});

test('dashboard shows recent APIs', function () {
    $api = Api::factory()->create(['name' => 'Test API']);

    get(route('dashboard'))
        ->assertOk()
        ->assertSee('Test API');
});

test('dashboard shows validation status breakdown', function () {
    $api = Api::factory()->create();
    $contract = Contract::factory()->for($api)->create();
    $version = ContractVersion::factory()->for($contract)->create();

    ValidationReport::factory()
        ->for($version, 'contractVersion')
        ->create(['status' => 'passed']);

    get(route('dashboard'))
        ->assertOk()
        ->assertSee('passed');
});

test('dashboard shows health score', function () {
    get(route('dashboard'))
        ->assertOk()
        ->assertSee('Health Score');
});
