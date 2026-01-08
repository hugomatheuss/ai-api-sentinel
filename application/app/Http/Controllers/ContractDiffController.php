<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Services\BreakingChangesDetector;
use App\Services\ContractParserService;
use Illuminate\Support\Facades\Storage;

/**
 * Controller para comparação visual de versões de contratos.
 *
 * Exibe diff lado a lado entre duas versões, highlighting de mudanças,
 * e análise detalhada de impacto.
 */
class ContractDiffController extends Controller
{
    public function __construct(
        protected ContractParserService $parser,
        protected BreakingChangesDetector $detector
    ) {}

    /**
     * Compare two contract versions side by side
     */
    public function compare(Contract $contract, ContractVersion $oldVersion, ContractVersion $newVersion)
    {
        // Load relationships
        $oldVersion->load('endpoints');
        $newVersion->load('endpoints');

        // Get breaking changes
        $breakingChanges = $this->detector->detect($oldVersion, $newVersion);
        $groupedChanges = $this->detector->groupByCategory($breakingChanges);
        $changeCounts = $this->detector->countBySeverity($breakingChanges);

        // Parse both contracts for detailed comparison
        $oldContent = null;
        $newContent = null;

        try {
            if (Storage::exists($oldVersion->file_path)) {
                $oldContent = Storage::get($oldVersion->file_path);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to load old version content', ['error' => $e->getMessage()]);
        }

        try {
            if (Storage::exists($newVersion->file_path)) {
                $newContent = Storage::get($newVersion->file_path);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to load new version content', ['error' => $e->getMessage()]);
        }

        // Compare endpoints
        $endpointComparison = $this->compareEndpoints($oldVersion, $newVersion);

        return view('contracts.diff', compact(
            'contract',
            'oldVersion',
            'newVersion',
            'breakingChanges',
            'groupedChanges',
            'changeCounts',
            'oldContent',
            'newContent',
            'endpointComparison'
        ));
    }

    /**
     * Compare endpoints between versions
     */
    protected function compareEndpoints(ContractVersion $oldVersion, ContractVersion $newVersion): array
    {
        $oldEndpoints = $oldVersion->endpoints->keyBy(fn ($e) => $e->method.' '.$e->path);
        $newEndpoints = $newVersion->endpoints->keyBy(fn ($e) => $e->method.' '.$e->path);

        $comparison = [
            'added' => [],
            'removed' => [],
            'modified' => [],
            'unchanged' => [],
        ];

        // Find removed endpoints
        foreach ($oldEndpoints as $key => $endpoint) {
            if (! $newEndpoints->has($key)) {
                $comparison['removed'][] = $endpoint;
            }
        }

        // Find added and modified endpoints
        foreach ($newEndpoints as $key => $endpoint) {
            if (! $oldEndpoints->has($key)) {
                $comparison['added'][] = $endpoint;
            } else {
                $oldEndpoint = $oldEndpoints->get($key);

                // Compare parameters and responses
                $hasChanges =
                    $oldEndpoint->parameters !== $endpoint->parameters ||
                    $oldEndpoint->responses !== $endpoint->responses ||
                    $oldEndpoint->request_body !== $endpoint->request_body;

                if ($hasChanges) {
                    $comparison['modified'][] = [
                        'old' => $oldEndpoint,
                        'new' => $endpoint,
                    ];
                } else {
                    $comparison['unchanged'][] = $endpoint;
                }
            }
        }

        return $comparison;
    }
}
