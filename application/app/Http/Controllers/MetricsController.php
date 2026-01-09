<?php

namespace App\Http\Controllers;

use App\Actions\CalculateSuccessRateAction;
use App\Actions\GetActivityTrendsAction;
use App\Actions\GetBreakingChangesTrendsAction;
use App\Actions\GetCommonIssuesAction;
use App\Actions\GetValidationTrendsAction;
use App\Models\ValidationReport;
use App\Services\CacheService;
use Illuminate\Http\Request;

/**
 * Metrics controller for advanced analytics and reporting.
 *
 * This controller coordinates the display of various metrics and analytics
 * for the API governance platform, delegating business logic to Actions.
 *
 * Why this exists:
 * - Provides a single entry point for the metrics dashboard
 * - Coordinates caching of expensive metrics calculations
 * - Handles HTTP concerns (request/response) while delegating business logic
 *
 * Callers should rely on:
 * - The index method returning a view with comprehensive metrics data
 */
class MetricsController extends Controller
{
    public function __construct(protected CacheService $cache) {}

    /**
     * Show metrics dashboard
     */
    public function index(Request $request)
    {
        $days = $request->get('days', 30);

        $metrics = $this->cache->remember("metrics:dashboard:{$days}", 600, function () use ($days) {
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

        // Use Actions for all business logic
        $validationTrends = app(GetValidationTrendsAction::class)->handle($days);
        $activityTrends = app(GetActivityTrendsAction::class)->handle($days);
        $breakingChangesTrends = app(GetBreakingChangesTrendsAction::class)->handle($days);
        $commonIssues = app(GetCommonIssuesAction::class)->handle($days);

        // Calculate success rate
        $totalValidations = ValidationReport::where('created_at', '>=', $startDate)->count();
        $passedValidations = ValidationReport::where('created_at', '>=', $startDate)
            ->where('status', 'passed')
            ->count();
        $successRate = app(CalculateSuccessRateAction::class)->handle($totalValidations, $passedValidations);

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
