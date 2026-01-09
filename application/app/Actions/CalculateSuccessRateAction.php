<?php

namespace App\Actions;

use App\Contracts\HandlesAction;

/**
 * Calculate success rate for validation reports.
 *
 * This action calculates the percentage of successful validations
 * based on the total number of reports and their status.
 *
 * Why extracted here:
 * - Centralizes success rate calculation logic
 * - Can be reused across controllers and reports
 * - Isolates the calculation formula for easier testing and updates
 *
 * Callers should rely on:
 * - Receiving a float between 0 and 100 representing the success percentage
 */
class CalculateSuccessRateAction implements HandlesAction
{
    /**
     * Calculate success rate from total and successful counts.
     *
     * @param  int  $total  Total number of items
     * @param  int  $successful  Number of successful items
     * @return float Success rate percentage (0-100)
     */
    public function handle(mixed ...$parameters): mixed
    {
        [$total, $successful] = $parameters;

        if ($total === 0) {
            return 0.0;
        }

        return round(($successful / $total) * 100, 2);
    }
}
