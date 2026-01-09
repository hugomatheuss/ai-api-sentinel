<?php

namespace App\Actions;

use App\Contracts\HandlesAction;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

/**
 * Get activity trends grouped by date and log type.
 *
 * This action retrieves activity log trends for a specified number of days,
 * grouping entries by date and log name to provide analytics data.
 *
 * Why extracted here:
 * - Encapsulates complex query logic for activity trends
 * - Reusable across different analytics and reporting contexts
 * - Isolates date-based grouping logic for consistent reporting
 *
 * Callers should rely on:
 * - Receiving a collection grouped by date with counts per log type
 */
class GetActivityTrendsAction implements HandlesAction
{
    /**
     * Get activity trends for the specified number of days.
     *
     * @param  mixed  ...$parameters  First parameter is the number of days (int)
     * @return \Illuminate\Support\Collection
     */
    public function handle(mixed ...$parameters): mixed
    {
        $days = $parameters[0];
        $startDate = now()->subDays($days);

        return ActivityLog::select(
            DB::raw('DATE(created_at) as date'),
            'log_name',
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date', 'log_name')
            ->orderBy('date')
            ->get()
            ->groupBy('date');
    }
}
