<?php

namespace Database\Factories;

use App\Models\ReportExpense;
use App\Models\ProgramReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportExpense>
 */
class ReportExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_report_id' => ProgramReport::factory(),
            'description' => fake()->sentence(3),
            'amount' => fake()->numberBetween(10000, 5000000),
            'spent_on' => fake()->date(),
            'receipt_path' => null,
        ];
    }
}
