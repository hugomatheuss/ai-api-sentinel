<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ValidationReport;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Metrics controller for advanced analytics and reporting.
 */
class MetricsController extends Controller
{
    public function __construct(protected CacheService $cache)
    {
    }

    /**
     * Show metrics dashboard
     */
    public function index(Request $request)
    {
        $days = $request->get('days', 30);

        $metrics = $this->cache->remember("metrics:dashboard:{$days}", 600, function() use ($days) {
            return $this->calculateMetrics($days);
        });

        return view('metrics.index', array_merge($metrics, ['days' => $days]));
    }

    /**
     * Calculate metrics for the dashboard
     */
    protected function calculateMetrics(int $days): array
    {
        $startDate = now()->subDays($days);

        // Validation trends (daily)
        $validationTrends = ValidationReport::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = \'passed\' THEN 1 ELSE 0 END) as passed'),
            DB::raw('SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failed')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Activity trends
        $activityTrends = ActivityLog::select(
            DB::raw('DATE(created_at) as date'),
            'log_name',
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date', 'log_name')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        // Breaking changes trends
        $breakingChangesTrends = ValidationReport::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereNotNull('breaking_changes')
            ->whereRaw('json_array_length(breaking_changes) > 0')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Most common issues
        $commonIssues = ValidationReport::whereNotNull('issues')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->flatMap(function($report) {
                return collect($report->issues ?? [])
                    ->pluck('type')
                    ->filter();
            })
            ->countBy()
            ->sortDesc()
            ->take(10);

        // Success rate
        $totalValidations = ValidationReport::where('created_at', '>=', $startDate)->count();
        $passedValidations = ValidationReport::where('created_at', '>=', $startDate)
            ->where('status', 'passed')
            ->count();
        $successRate = $totalValidations > 0 ? round(($passedValidations / $totalValidations) * 100, 1) : 0;

        return [
            'validationTrends' => $validationTrends,
            'activityTrends' => $activityTrends,
            'breakingChangesTrends' => $breakingChangesTrends,
            'commonIssues' => $commonIssues,
            'successRate' => $successRate,
            'totalValidations' => $totalValidations,
        ];
    }
}

