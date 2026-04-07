<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::first()->id,
            'staff_number' => fake()->numberBetween(10, 20) . str_pad(fake()->numberBetween(0, 9999), 4, '0', STR_PAD_LEFT),
            'department' => 'Office of Student Discipline',
            'school_id' => School::first()->id,
        ];
    }
}
