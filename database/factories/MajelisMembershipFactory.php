<?php

namespace Database\Factories;

use App\Models\Majelis;
use App\Models\MajelisMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MajelisMembership>
 */
class MajelisMembershipFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => 'anggota',
            'position' => 'Anggota',
            'is_active' => true,
        ];
    }
}
