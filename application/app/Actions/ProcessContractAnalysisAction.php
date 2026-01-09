<?php

namespace App\Actions;

use App\Contracts\HandlesAction;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use App\Services\ActivityLogger;
use App\Services\AIAnalysisService;
use App\Services\BreakingChangesDetector;
use App\Services\ContractParserService;
use App\Services\ContractValidatorService;
use Illuminate\Support\Facades\Storage;

/**
 * Process and analyze a contract version.
 *
 * This action orchestrates the complete analysis workflow for a contract version,
 * including parsing, validation, AI analysis, breaking change detection, and reporting.
 *
 * Why extracted here:
 * - Encapsulates complex multi-step analysis workflow
 * - Makes the controller thin and focused on HTTP concerns
 * - Enables reuse in queue jobs, CLI commands, or API endpoints
 * - Centralizes all analysis business logic for easier testing
 *
 * Callers should rely on:
 * - Receiving a ValidationReport with complete analysis results
 * - The action handling all errors and returning appropriate status
 */
class ProcessContractAnalysisAction implements HandlesAction
{
    public function __construct(
        protected ContractParserService $parser,
        protected ContractValidatorService $validator,
        protected BreakingChangesDetector $breakingChangesDetector,
        protected AIAnalysisService $aiAnalysis,
        protected ActivityLogger $logger
    ) {}

    /**
     * Process contract analysis.
     *
     * @param  mixed  ...$parameters  First: Contract, Second: ContractVersion
     * @return ValidationReport
     */
    public function handle(mixed ...$parameters): mixed
    {
        [$contract, $version] = $parameters;

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

            // AI-powered analysis (uses LLM if available, fallback to rules)
            $aiInsights = $this->aiAnalysis->analyzeContract($version);
            $qualityScore = $this->aiAnalysis->calculateQualityScore($version);

            // Save quality score in version metadata
            $currentMetadata = $version->metadata ?? [];
            $version->update([
                'metadata' => array_merge($currentMetadata, [
                    'quality_score' => $qualityScore,
                    'ai_analysis_date' => now()->toIso8601String(),
                ]),
            ]);

            // Merge AI insights with validation issues
            $issues = array_merge($issues, $aiInsights);

            // Get previous version for comparison
            $previousVersion = ContractVersion::where('contract_id', $contract->id)
                ->where('id', '<', $version->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($previousVersion && $previousVersion->file_path) {
                // Compare versions and detect breaking changes
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

            // Log activity
            $this->logger
                ->log('validation', "Contract {$contract->title} v{$version->version} validated")
                ->on($version)
                ->event($status === 'failed' ? 'validation_failed' : 'validation_passed')
                ->withProperties([
                    'contract_id' => $contract->id,
                    'version' => $version->version,
                    'error_count' => $counts['error'],
                    'warning_count' => $counts['warning'],
                    'breaking_changes_count' => count($breakingChanges),
                ]);

            return $report;

        } catch (\Exception $e) {
            // Handle parsing errors
            return ValidationReport::create([
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
    }
}
