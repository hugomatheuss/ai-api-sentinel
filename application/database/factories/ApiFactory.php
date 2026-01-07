<?php

namespace Database\Factories;

use App\Models\Api;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Api>
 */
class ApiFactory extends Factory
{
    protected $model = Api::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' API',
            'description' => fake()->sentence(12),
            'base_url' => fake()->url(),
            'owner' => fake()->name(),
            'status' => fake()->randomElement(['active', 'deprecated', 'retired']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function deprecated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'deprecated',
        ]);
    }
}
