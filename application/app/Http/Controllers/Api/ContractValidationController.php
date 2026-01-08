<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Services\BreakingChangesDetector;
use App\Services\ContractParserService;
use App\Services\ContractValidatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * API Controller for contract validation.
 *
 * Provides REST endpoints for CI/CD integration, allowing automated
 * validation of OpenAPI contracts in pipelines.
 */
class ContractValidationController extends Controller
{
    public function __construct(
        protected ContractParserService $parser,
        protected ContractValidatorService $validator,
        protected BreakingChangesDetector $detector
    ) {}

    /**
     * Validate an OpenAPI contract file.
     *
     * POST /api/v1/validate
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:yaml,yml,json|max:2048',
            'contract_id' => 'sometimes|exists:contracts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            // Parse OpenAPI contract
            $openapi = $this->parser->parse($file->getRealPath(), $extension);

            // Validate structure
            $issues = $this->validator->validate($openapi);
            $status = $this->validator->determineStatus($issues);
            $counts = $this->validator->countBySeverity($issues);

            // Extract metadata
            $metadata = $this->parser->extractMetadata($openapi);
            $endpoints = $this->parser->extractEndpoints($openapi);

            return response()->json([
                'success' => true,
                'status' => $status,
                'metadata' => $metadata,
                'validation' => [
                    'status' => $status,
                    'error_count' => $counts['error'],
                    'warning_count' => $counts['warning'],
                    'info_count' => $counts['info'],
                    'issues' => $issues,
                ],
                'endpoints' => [
                    'count' => count($endpoints),
                    'list' => $endpoints,
                ],
            ], $status === 'failed' ? 422 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to parse OpenAPI contract',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Compare two contract versions for breaking changes.
     *
     * POST /api/v1/compare
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function compare(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_version_id' => 'required|exists:contract_versions,id',
            'new_version_id' => 'required|exists:contract_versions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $oldVersion = ContractVersion::findOrFail($request->old_version_id);
            $newVersion = ContractVersion::findOrFail($request->new_version_id);

            // Detect breaking changes
            $breakingChanges = $this->detector->detect($oldVersion, $newVersion);
            $grouped = $this->detector->groupByCategory($breakingChanges);
            $counts = $this->detector->countBySeverity($breakingChanges);

            $hasBlockingChanges = $counts['critical'] > 0;

            return response()->json([
                'success' => true,
                'comparison' => [
                    'old_version' => $oldVersion->version,
                    'new_version' => $newVersion->version,
                    'has_breaking_changes' => ! empty($breakingChanges),
                    'has_blocking_changes' => $hasBlockingChanges,
                ],
                'breaking_changes' => [
                    'total' => count($breakingChanges),
                    'critical' => $counts['critical'],
                    'warning' => $counts['warning'],
                    'info' => $counts['info'],
                    'by_category' => $grouped,
                    'all' => $breakingChanges,
                ],
                'recommendation' => $hasBlockingChanges
                    ? 'BLOCK: Critical breaking changes detected'
                    : ($counts['warning'] > 0
                        ? 'WARN: Non-critical changes detected, review recommended'
                        : 'PASS: No breaking changes detected'),
            ], $hasBlockingChanges ? 422 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to compare versions',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get validation status of a contract version.
     *
     * GET /api/v1/contracts/{contract}/versions/{version}/status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Contract $contract, ContractVersion $version)
    {
        $version->load(['validationReports', 'endpoints']);

        $latestReport = $version->validationReports()
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $latestReport) {
            return response()->json([
                'success' => false,
                'message' => 'No validation report found. Please run analysis first.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'contract' => [
                'id' => $contract->id,
                'title' => $contract->title,
            ],
            'version' => [
                'id' => $version->id,
                'version' => $version->version,
                'status' => $version->status,
                'created_at' => $version->created_at->toIso8601String(),
            ],
            'validation' => [
                'status' => $latestReport->status,
                'error_count' => $latestReport->error_count,
                'warning_count' => $latestReport->warning_count,
                'issues' => $latestReport->issues,
                'breaking_changes' => $latestReport->breaking_changes,
                'processed_at' => $latestReport->processed_at?->toIso8601String(),
            ],
            'endpoints' => [
                'count' => $version->endpoints->count(),
            ],
        ]);
    }

    /**
     * Health check endpoint.
     *
     * GET /api/v1/health
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'API Sentinel',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
