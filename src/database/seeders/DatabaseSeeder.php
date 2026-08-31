<?php

namespace Database\Seeders;

use App\Models\GradeSection;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // php artisan migrate:fresh --seed
    // php artisan db:seed
    // docker compose exec app php artisan migrate:fresh --seed

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /* User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */

        User::factory(20)->create();
        $this->call(StaffSeeder::class);

        $schoolYear = SchoolYear::factory()->create();
        $this->call(GradeSectionSeeder::class);

        $students = Student::factory(50)->create();

        $sections = GradeSection::all();
        foreach ($students as $student) {
            $section = $sections->random();
            DB::table('enrollments')->insert([
                'student_id' => $student->id,
                'grade_section_id' => $section->id,
                'school_year_id' => $schoolYear->id,
                'status' => 'Enrolled',
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->call(ParentSeeder::class);
        $this->call(ReportSeeder::class);
    }
}
