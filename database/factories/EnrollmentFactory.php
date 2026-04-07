<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::first()->id,
            'school_year_id' => SchoolYear::first()->id,
            'grade_level' => 'Grade 7',
            'section' => 'Genesis',
        ];
    }
}
