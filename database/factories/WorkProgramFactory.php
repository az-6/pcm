<?php

namespace Database\Factories;

use App\Enums\WorkProgramStatus;
use App\Models\Majelis;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkProgram>
 */
class WorkProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'majelis_id' => Majelis::factory(),
            'created_by' => User::factory(),
            'name' => fake()->sentence(4),
            'code' => fake()->unique()->bothify('PRG-####'),
            'description' => fake()->paragraph(),
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->addMonths(3)->endOfMonth(),
            'status' => WorkProgramStatus::Draft,
            'budget' => fake()->numberBetween(1000000, 50000000),
        ];
    }
}
