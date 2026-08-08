<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\ProgramReport;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramReport>
 */
class ProgramReportFactory extends Factory
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
            'reference' => fake()->unique()->bothify('RPT-####'),
            'title' => fake()->sentence(4),
            'summary' => fake()->paragraph(),
            'realized_amount' => fake()->numberBetween(100000, 20000000),
            'status' => ReportStatus::Draft,
            'reviewer_note' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
        ];
    }
}
