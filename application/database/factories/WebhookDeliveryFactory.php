<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'event' => fake()->randomElement(['contract.validated', 'contract.failed', 'breaking_changes.detected']),
            'payload' => [
                'event' => fake()->word(),
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'contract_id' => fake()->numberBetween(1, 100),
                    'version' => fake()->semver(),
                ],
            ],
            'status_code' => 200,
            'response_body' => json_encode(['status' => 'ok']),
            'attempt' => 1,
            'success' => true,
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the delivery failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'status_code' => 500,
            'error_message' => 'Internal Server Error',
        ]);
    }

    /**
     * Set a specific attempt number.
     */
    public function attempt(int $attempt): static
    {
        return $this->state(fn (array $attributes) => [
            'attempt' => $attempt,
        ]);
    }
}
