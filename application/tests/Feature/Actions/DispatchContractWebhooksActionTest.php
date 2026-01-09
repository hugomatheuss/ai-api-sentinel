<?php

use App\Actions\DispatchContractWebhooksAction;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    $this->action = app(DispatchContractWebhooksAction::class);
});

test('dispatches contract failed webhook when status is failed', function () {
    $webhook = Webhook::factory()->create([
        'events' => ['contract.failed'],
        'active' => true,
    ]);

    $contract = Contract::factory()->create(['title' => 'Test API']);
    $version = ContractVersion::factory()->for($contract)->create(['version' => '1.0.0']);
    $report = ValidationReport::factory()->for($version)->create([
        'status' => 'failed',
        'error_count' => 5,
        'warning_count' => 3,
    ]);

    $this->action->handle($contract, $version, $report, []);

    Http::assertSent(function ($request) use ($webhook) {
        return $request->url() === $webhook->url &&
               $request['event'] === 'contract.failed';
    });
});

test('dispatches contract validated webhook when status is passed', function () {
    $webhook = Webhook::factory()->create([
        'events' => ['contract.validated'],
        'active' => true,
    ]);

    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();
    $report = ValidationReport::factory()->for($version)->create([
        'status' => 'passed',
        'error_count' => 0,
        'warning_count' => 2,
    ]);

    $this->action->handle($contract, $version, $report, []);

    Http::assertSent(function ($request) use ($webhook) {
        return $request->url() === $webhook->url &&
               $request['event'] === 'contract.validated';
    });
});

test('dispatches breaking changes webhook when breaking changes detected', function () {
    $webhook = Webhook::factory()->create([
        'events' => ['breaking_changes.detected'],
        'active' => true,
    ]);

    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();
    $report = ValidationReport::factory()->for($version)->create(['status' => 'passed']);

    $breakingChanges = [
        ['type' => 'removed_endpoint', 'severity' => 'critical', 'path' => '/users'],
        ['type' => 'changed_type', 'severity' => 'major', 'path' => '/posts'],
    ];

    $this->action->handle($contract, $version, $report, $breakingChanges);

    Http::assertSent(function ($request) use ($webhook) {
        return $request->url() === $webhook->url &&
               $request['event'] === 'breaking_changes.detected' &&
               $request['data']['total_changes'] === 2;
    });
});

test('includes critical changes count in breaking changes webhook', function () {
    $webhook = Webhook::factory()->create([
        'events' => ['breaking_changes.detected'],
        'active' => true,
    ]);

    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();
    $report = ValidationReport::factory()->for($version)->create(['status' => 'passed']);

    $breakingChanges = [
        ['type' => 'removed_endpoint', 'severity' => 'critical'],
        ['type' => 'changed_type', 'severity' => 'major'],
        ['type' => 'removed_field', 'severity' => 'critical'],
    ];

    $this->action->handle($contract, $version, $report, $breakingChanges);

    Http::assertSent(function ($request) {
        return $request['data']['critical_changes'] === 2;
    });
});

test('does not dispatch webhooks when none are configured', function () {
    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();
    $report = ValidationReport::factory()->for($version)->create(['status' => 'failed']);

    $this->action->handle($contract, $version, $report, []);

    Http::assertNothingSent();
});

test('includes report url in webhook payload', function () {
    $webhook = Webhook::factory()->create([
        'events' => ['contract.validated'],
        'active' => true,
    ]);

    $contract = Contract::factory()->create();
    $version = ContractVersion::factory()->for($contract)->create();
    $report = ValidationReport::factory()->for($version)->create(['status' => 'passed']);

    $this->action->handle($contract, $version, $report, []);

    Http::assertSent(function ($request) use ($contract, $version) {
        return isset($request['data']['report_url']) &&
               str_contains($request['data']['report_url'], (string) $contract->id) &&
               str_contains($request['data']['report_url'], (string) $version->id);
    });
});
