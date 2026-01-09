<?php

namespace App\Http\Controllers;

use App\Actions\DispatchContractWebhooksAction;
use App\Actions\ProcessContractAnalysisAction;
use App\Models\Contract;
use App\Models\ContractVersion;

/**
 * Handles analysis and validation of API contracts.
 *
 * This controller orchestrates the analysis of OpenAPI contracts,
 * including structural validation, breaking change detection,
 * and generation of validation reports.
 *
 * Why this exists:
 * - Provides HTTP endpoints for contract analysis workflow
 * - Delegates business logic to Actions for reusability
 * - Handles routing and view rendering
 *
 * Callers should rely on:
 * - Clean HTTP interface for contract analysis operations
 */
class ContractAnalysisController extends Controller
{
    public function __construct(
        protected ProcessContractAnalysisAction $processAnalysis,
        protected DispatchContractWebhooksAction $dispatchWebhooks
    ) {}

    /**
     * Show the analysis overview page for a contract version.
     */
    public function show(Contract $contract, ContractVersion $version)
    {
        $version->load(['endpoints', 'validationReports']);
        // Get previous version for comparison if exists
        $previousVersion = ContractVersion::where('contract_id', $contract->id)
            ->where('id', '<', $version->id)
            ->orderBy('id', 'desc')
            ->first();

        return view('contract-versions.analyze', compact('contract', 'version', 'previousVersion'));
    }

    /**
     * Process and analyze a contract version.
     */
    public function process(Contract $contract, ContractVersion $version)
    {
        // TODO: Queue job for async processing
        // For now, we'll do synchronous processing
        $report = $this->processAnalysis->handle($contract, $version);
        // Dispatch webhooks
        $this->dispatchWebhooks->handle($contract, $version, $report, $report->breaking_changes ?? []);

        return redirect()
            ->route('contracts.versions.report', ['contract' => $contract->id, 'version' => $version->id])
            ->with('success', 'Contract analysis completed successfully.');
    }

    /**
     * Show the analysis report for a contract version.
     */
    public function report(Contract $contract, ContractVersion $version)
    {
        $version->load(['endpoints', 'validationReports']);
        $latestReport = $version->validationReports()
            ->orderBy('created_at', 'desc')
            ->first();
        // Get previous version for comparison
        $previousVersion = ContractVersion::where('contract_id', $contract->id)
            ->where('id', '<', $version->id)
            ->orderBy('id', 'desc')
            ->first();

        return view('contract-versions.report', compact('contract', 'version', 'latestReport', 'previousVersion'));
    }
}
