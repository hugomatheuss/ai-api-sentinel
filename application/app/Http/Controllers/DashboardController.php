<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard controller for API governance overview.
 *
 * Displays metrics, health status, and governance indicators
 * for all APIs managed in the platform.
 */
class DashboardController extends Controller
{
    /**
     * Show the dashboard with governance metrics
     */
    public function index()
    {
        // Total counts
        $totalApis = Api::count();
        $totalContracts = Contract::count();
        $totalVersions = ContractVersion::count();
        $totalReports = ValidationReport::count();

        // API status breakdown
        $apisByStatus = Api::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Recent APIs
        $recentApis = Api::with('contracts')
            ->latest()
            ->take(5)
            ->get();

        // Validation reports status
        $reportsByStatus = ValidationReport::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Recent validation reports
        $recentReports = ValidationReport::with('contractVersion.contract.api')
            ->latest('created_at')
            ->take(10)
            ->get();

        // APIs with issues
        $apisWithIssues = ValidationReport::where('status', 'failed')
            ->with('contractVersion.contract.api')
            ->latest()
            ->take(5)
            ->get();

        // Breaking changes detected recently
        $recentBreakingChanges = ValidationReport::whereNotNull('breaking_changes')
            ->whereRaw('json_array_length(breaking_changes) > 0')
            ->with('contractVersion.contract.api')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'totalApis',
            'totalContracts',
            'totalVersions',
            'totalReports',
            'apisByStatus',
            'recentApis',
            'reportsByStatus',
            'recentReports',
            'apisWithIssues',
            'recentBreakingChanges'
        ));
    }
}
