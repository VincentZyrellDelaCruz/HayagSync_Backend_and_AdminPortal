<?php

namespace Database\Factories;

use App\Models\GradeSection;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        $grade_section = GradeSection::inRandomOrder()->first()?->id;

        return [
            'student_number' => fake()->numberBetween(20, 25) . '-' .
                            str_pad(fake()->numberBetween(0, 99999), 5, '0', STR_PAD_LEFT) .
                            '-' . str_pad(fake()->numberBetween(0, 999), 3, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => fake()->randomElement(['Male', 'Female']),
            'birthdate' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'email' => Str::replace(' ', '', Str::lower($firstName)) . '.' .
                        Str::replace(' ', '', Str::lower($lastName)) . '@neu.edu.ph',
        ];
    }
}
