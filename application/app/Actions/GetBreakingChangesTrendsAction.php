<?php

namespace App\Actions;

use App\Contracts\HandlesAction;
use App\Models\ValidationReport;
use Illuminate\Support\Facades\DB;

/**
 * Get breaking changes trends over time.
 *
 * This action retrieves trends of breaking changes detected in validation reports
 * for a specified number of days, useful for tracking API evolution impact.
 *
 * Why extracted here:
 * - Isolates breaking changes trend analysis logic
 * - Provides reusable analytics for governance dashboards
 * - Centralizes the logic for counting breaking changes over time
 *
 * Callers should rely on:
 * - Receiving a collection with date and count of breaking changes per day
 */
class GetBreakingChangesTrendsAction implements HandlesAction
{
    /**
     * Get breaking changes trends for the specified number of days.
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
            DB::raw('COUNT(*) as count')
        )
            ->whereNotNull('breaking_changes')
            ->whereRaw('json_array_length(breaking_changes) > 0')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
