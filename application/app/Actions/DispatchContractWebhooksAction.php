<?php

namespace App\Actions;

use App\Contracts\HandlesAction;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use App\Services\WebhookService;

/**
 * Dispatch webhooks for contract validation events.
 *
 * This action handles the dispatching of webhooks when contract validations
 * complete, including different events for failures and breaking changes.
 *
 * Why extracted here:
 * - Isolates webhook dispatching logic from controller
 * - Makes webhook logic reusable across different contexts
 * - Centralizes event naming and payload structure
 * - Simplifies testing of webhook dispatch logic
 *
 * Callers should rely on:
 * - All appropriate webhooks being dispatched based on validation results
 * - Consistent event names and payload structure
 */
class DispatchContractWebhooksAction implements HandlesAction
{
    public function __construct(protected WebhookService $webhookService) {}

    /**
     * Dispatch contract validation webhooks.
     *
     * @param  mixed  ...$parameters  Contract, ContractVersion, ValidationReport, breaking changes array
     * @return void
     */
    public function handle(mixed ...$parameters): mixed
    {
        [$contract, $version, $report, $breakingChanges] = $parameters;

        // Dispatch appropriate event based on validation result
        if ($report->status === 'failed') {
            $this->webhookService->dispatch('contract.failed', [
                'contract_id' => $contract->id,
                'contract_title' => $contract->title,
                'version' => $version->version,
                'error_count' => $report->error_count,
                'warning_count' => $report->warning_count,
                'report_url' => route('contracts.versions.report', ['contract' => $contract->id, 'version' => $version->id]),
            ]);
        } else {
            $this->webhookService->dispatch('contract.validated', [
                'contract_id' => $contract->id,
                'contract_title' => $contract->title,
                'version' => $version->version,
                'status' => $report->status,
                'error_count' => $report->error_count,
                'warning_count' => $report->warning_count,
                'report_url' => route('contracts.versions.report', ['contract' => $contract->id, 'version' => $version->id]),
            ]);
        }

        // Dispatch breaking changes event if detected
        if (! empty($breakingChanges)) {
            $criticalCount = collect($breakingChanges)->where('severity', 'critical')->count();

            $this->webhookService->dispatch('breaking_changes.detected', [
                'contract_id' => $contract->id,
                'contract_title' => $contract->title,
                'version' => $version->version,
                'total_changes' => count($breakingChanges),
                'critical_changes' => $criticalCount,
                'changes' => $breakingChanges,
                'report_url' => route('contracts.versions.report', ['contract' => $contract->id, 'version' => $version->id]),
            ]);
        }

        return null;
    }
}
