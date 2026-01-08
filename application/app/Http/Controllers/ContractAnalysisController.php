<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Http\Request;

/**
 * Handles analysis and validation of API contracts.
 *
 * This controller orchestrates the analysis of OpenAPI contracts,
 * including structural validation, breaking change detection,
 * and generation of validation reports.
 */
class ContractAnalysisController extends Controller
{
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

        $issues = [];
        $breakingChanges = [];

        // Basic validation
        if (!$version->openapi_content) {
            $issues[] = [
                'severity' => 'error',
                'message' => 'No OpenAPI content found',
                'type' => 'missing_content'
            ];
        }

        // Get previous version for comparison
        $previousVersion = ContractVersion::where('contract_id', $contract->id)
            ->where('id', '<', $version->id)
            ->orderBy('id', 'desc')
            ->first();

        if ($previousVersion) {
            // Compare versions and detect breaking changes
            $breakingChanges = $this->detectBreakingChanges($previousVersion, $version);
        }

        // Create validation report
        $report = ValidationReport::create([
            'contract_version_id' => $version->id,
            'status' => empty($issues) && empty($breakingChanges) ? 'passed' : 'failed',
            'issues' => $issues,
            'breaking_changes' => $breakingChanges,
            'processed_at' => now(),
        ]);

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

    /**
     * Detect breaking changes between two contract versions.
     */
    protected function detectBreakingChanges(ContractVersion $oldVersion, ContractVersion $newVersion): array
    {
        $breakingChanges = [];

        $oldEndpoints = $oldVersion->endpoints->keyBy('path_method');
        $newEndpoints = $newVersion->endpoints->keyBy('path_method');

        // Check for removed endpoints
        foreach ($oldEndpoints as $key => $oldEndpoint) {
            if (!$newEndpoints->has($key)) {
                $breakingChanges[] = [
                    'type' => 'endpoint_removed',
                    'severity' => 'critical',
                    'message' => "Endpoint removed: {$oldEndpoint->method} {$oldEndpoint->path}",
                    'endpoint' => $oldEndpoint->path,
                    'method' => $oldEndpoint->method,
                ];
            }
        }

        // Check for modified endpoints
        foreach ($oldEndpoints as $key => $oldEndpoint) {
            if ($newEndpoints->has($key)) {
                $newEndpoint = $newEndpoints->get($key);

                // Compare parameters, responses, etc.
                // This is a simplified check - you'd want more thorough comparison
                if ($oldEndpoint->parameters !== $newEndpoint->parameters) {
                    $breakingChanges[] = [
                        'type' => 'parameters_changed',
                        'severity' => 'warning',
                        'message' => "Parameters changed for: {$newEndpoint->method} {$newEndpoint->path}",
                        'endpoint' => $newEndpoint->path,
                        'method' => $newEndpoint->method,
                    ];
                }
            }
        }

        return $breakingChanges;
    }
}
