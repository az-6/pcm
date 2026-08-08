<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\FundSubmission;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundSubmission>
 */
class FundSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_program_id' => WorkProgram::factory(),
            'submitted_by' => User::factory(),
            'reference' => fake()->unique()->bothify('SUB-####'),
            'title' => fake()->sentence(4),
            'purpose' => fake()->paragraph(),
            'amount' => fake()->numberBetween(100000, 20000000),
            'status' => SubmissionStatus::Draft,
            'reviewer_note' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
        ];
    }
}
