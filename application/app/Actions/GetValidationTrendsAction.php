<?php

namespace App\Actions;

use App\Contracts\HandlesAction;
use App\Models\ValidationReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Get validation trends over time.
 *
 * This action retrieves validation trends showing passed vs failed validations
 * over a specified time period, essential for monitoring API quality.
 *
 * Why extracted here:
 * - Encapsulates complex SQL aggregation for validation statistics
 * - Provides consistent trend data across different views
 * - Centralizes the logic for calculating validation success/failure rates
 *
 * Callers should rely on:
 * - Receiving a collection with date, total, passed, and failed counts per day
 */
class GetValidationTrendsAction implements HandlesAction
{
    /**
     * Get validation trends for the specified number of days.
     *
     * @param  mixed  ...$parameters  First parameter is the number of days (int)
     * @return \Illuminate\Support\Collection
     */
    public function handle(mixed ...$parameters): mixed
    {
        $days = $parameters[0];
        $startDate = now()->subDays($days);

        return ValidationReport::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = \'passed\' THEN 1 ELSE 0 END) as passed'),
            DB::raw('SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failed')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
