<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use App\Services\AIAnalysisService;
use App\Services\BreakingChangesDetector;
use App\Services\ContractParserService;
use App\Services\ContractValidatorService;
use Illuminate\Support\Facades\Storage;

/**
 * Handles analysis and validation of API contracts.
 *
 * This controller orchestrates the analysis of OpenAPI contracts,
 * including structural validation, breaking change detection,
 * and generation of validation reports.
 */
class ContractAnalysisController extends Controller
{
    public function __construct(
        protected ContractParserService $parser,
        protected ContractValidatorService $validator,
        protected BreakingChangesDetector $breakingChangesDetector,
        protected AIAnalysisService $aiAnalysis
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

        $issues = [];
        $breakingChanges = [];

        try {
            // Check if file exists
            if (! Storage::exists($version->file_path)) {
                \Log::error('Contract file not found', [
                    'version_id' => $version->id,
                    'file_path' => $version->file_path,
                    'storage_root' => storage_path('app'),
                    'full_path' => Storage::path($version->file_path),
                ]);
                throw new \Exception('Contract file not found in storage. Path: '.$version->file_path);
            }

            // Get real path of the stored file
            $filePath = Storage::path($version->file_path);
            $extension = pathinfo($version->file_path, PATHINFO_EXTENSION);

            \Log::info('Parsing contract', [
                'file_path' => $filePath,
                'extension' => $extension,
                'file_exists' => file_exists($filePath),
            ]);

            $openapi = $this->parser->parse($filePath, $extension);

            // Validate contract structure
            $issues = $this->validator->validate($openapi);

            // AI-powered analysis
            $aiInsights = $this->aiAnalysis->analyzeNaming($version);
            $qualityScore = $this->aiAnalysis->calculateQualityScore($version);

            // Merge AI insights with validation issues
            $issues = array_merge($issues, $aiInsights);

            // Get previous version for comparison
            $previousVersion = ContractVersion::where('contract_id', $contract->id)
                ->where('id', '<', $version->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($previousVersion && $previousVersion->file_path) {
                // Compare versions and detect breaking changes using dedicated service
                $breakingChanges = $this->breakingChangesDetector->detect($previousVersion, $version);
            }

            // Determine status based on issues
            $status = $this->validator->determineStatus($issues);

            // If there are breaking changes, mark as failed
            if (! empty($breakingChanges)) {
                $criticalChanges = array_filter($breakingChanges, fn ($change) => $change['severity'] === 'critical');
                if (! empty($criticalChanges)) {
                    $status = 'failed';
                }
            }

            $counts = $this->validator->countBySeverity($issues);

            // Create validation report
            $report = ValidationReport::create([
                'contract_version_id' => $version->id,
                'status' => $status,
                'issues' => $issues,
                'breaking_changes' => $breakingChanges,
                'error_count' => $counts['error'],
                'warning_count' => $counts['warning'],
                'processed_at' => now(),
            ]);

        } catch (\Exception $e) {
            // Handle parsing errors
            $report = ValidationReport::create([
                'contract_version_id' => $version->id,
                'status' => 'failed',
                'issues' => [
                    [
                        'severity' => 'error',
                        'type' => 'parse_error',
                        'message' => 'Failed to parse OpenAPI contract: '.$e->getMessage(),
                        'path' => 'root',
                    ],
                ],
                'breaking_changes' => [],
                'error_count' => 1,
                'warning_count' => 0,
                'processed_at' => now(),
            ]);
        }

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
