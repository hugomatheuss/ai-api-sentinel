<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContractVersion>
 */
class ContractVersionFactory extends Factory
{
    protected $model = ContractVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'version' => fake()->numerify('#.#.#'), // SemVer format
            'file_path' => 'contracts/' . fake()->uuid() . '.yaml',
            'checksum' => hash('sha256', fake()->text(500)),
            'status' => fake()->randomElement(['pending', 'validated', 'failed']),
            'metadata' => [
                'openapi' => '3.0.0',
                'servers' => [['url' => fake()->url()]],
            ],
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validated',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
