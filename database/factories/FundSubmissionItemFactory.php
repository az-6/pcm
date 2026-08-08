<?php

namespace Database\Factories;

use App\Models\FundSubmission;
use App\Models\FundSubmissionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundSubmissionItem>
 */
class FundSubmissionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fund_submission_id' => FundSubmission::factory(),
            'description' => fake()->sentence(3),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->numberBetween(10000, 5000000),
            'supporting_document' => null,
        ];
    }
}
