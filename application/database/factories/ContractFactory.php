<?php

namespace Database\Factories;

use App\Models\Api;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'api_id' => Api::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
        ];
    }
}
