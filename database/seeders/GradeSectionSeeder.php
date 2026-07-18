<?php

namespace Database\Seeders;

use App\Models\GradeSection;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GradeSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school_year = SchoolYear::first()->id;

        // $grades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10']

        $sections = ['Ecclesiastes', 'Genesis', 'Jeremiah', 'Ezekiel', 'Joshua', 'Psalms', 'Malachi'];

        foreach ($sections as $section) {
            $adviser = User::whereHas('staff.positions', function ($q) {
                $q->where('position_name', 'Teacher');
            })->inRandomOrder()->first();

            GradeSection::create([
                'school_year_id' => $school_year ,
                'grade_level' => 'Grade 7',
                'section' => $section,
                'adviser' => $adviser?->id,
            ]);
        }
    }
}
