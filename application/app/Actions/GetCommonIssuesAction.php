<?php

namespace App\Actions;

use App\Contracts\HandlesAction;
use App\Models\ValidationReport;

/**
 * Get most common validation issues.
 *
 * This action analyzes validation reports to identify the most frequently
 * occurring issue types, providing insights for quality improvements.
 *
 * Why extracted here:
 * - Encapsulates complex aggregation logic for issue analysis
 * - Reusable across analytics and reporting features
 * - Centralizes the logic for identifying common API contract problems
 *
 * Callers should rely on:
 * - Receiving the top 10 most common issue types with their occurrence counts
 */
class GetCommonIssuesAction implements HandlesAction
{
    /**
     * Get the most common issues for the specified number of days.
     *
     * @param  mixed  ...$parameters  First parameter is the number of days (int)
     * @return \Illuminate\Support\Collection
     */
    public function handle(mixed ...$parameters): mixed
    {
        $days = $parameters[0];
        $startDate = now()->subDays($days);

        return ValidationReport::whereNotNull('issues')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->flatMap(function ($report) {
                return collect($report->issues ?? [])
                    ->pluck('type')
                    ->filter();
            })
            ->countBy()
            ->sortDesc()
            ->take(10);
    }
}
