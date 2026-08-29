<?php

namespace Database\Factories;

use App\Models\GradeSection;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeSection>
 */
class GradeSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $school_year = SchoolYear::first()?->id ?? SchoolYear::factory()->create()->id;
        $adviser = User::whereHas('staff.positions', function ($q) {
            $q->where('position_name', 'Teacher');
        })->inRandomOrder()->first();

        return [
            'school_year_id' => $school_year ,
            'grade_level' => 'Grade 7',
            'section' => fake()->randomElement(['Genesis', 'Ecclesiastes', 'Jeremiah']),
            'adviser' => $adviser,
        ];
    }
}
