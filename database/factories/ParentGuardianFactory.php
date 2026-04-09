<?php

namespace Database\Factories;

use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParentGuardian>
 */
class ParentGuardianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::oldest()->first()->id,
            'parent_code' => 'PG-' . str_pad(fake()->numberBetween(0, 999999), 6, '0', STR_PAD_LEFT),
            'occupation' => fake()->jobTitle(),
        ];
    }
}
