<?php

namespace Database\Factories;

use App\Models\ContractVersion;
use App\Models\ValidationReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ValidationReport>
 */
class ValidationReportFactory extends Factory
{
    protected $model = ValidationReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $errorCount = fake()->numberBetween(0, 10);
        $warningCount = fake()->numberBetween(0, 15);
        $status = $errorCount > 0 ? 'failed' : ($warningCount > 0 ? 'warning' : 'passed');

        return [
            'contract_version_id' => ContractVersion::factory(),
            'status' => $status,
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'report_json' => [
                'errors' => $this->generateIssues($errorCount, 'error'),
                'warnings' => $this->generateIssues($warningCount, 'warning'),
                'summary' => "Validation completed with {$errorCount} errors and {$warningCount} warnings",
            ],
            'issues' => $this->generateIssues($errorCount, 'error'),
            'breaking_changes' => [],
            'processed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    private function generateIssues(int $count, string $type): array
    {
        $issues = [];
        for ($i = 0; $i < $count; $i++) {
            $issues[] = [
                'type' => $type,
                'path' => fake()->randomElement(['paths./users.get', 'components.schemas.User', 'info.version']),
                'message' => fake()->sentence(8),
            ];
        }

        return $issues;
    }

    public function pass(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'passed',
            'error_count' => 0,
            'warning_count' => 0,
            'report_json' => [
                'errors' => [],
                'warnings' => [],
                'summary' => 'Validation passed successfully',
            ],
            'issues' => [],
            'breaking_changes' => [],
        ]);
    }

    public function fail(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_count' => fake()->numberBetween(1, 10),
        ]);
    }
}
