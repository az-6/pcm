<?php

namespace Database\Factories;

use App\Models\Majelis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Majelis>
 */
class MajelisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->lexify('???'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
