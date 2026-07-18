<?php

namespace Database\Seeders;

use App\Models\GradeSection;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // php artisan migrate:fresh --seed
    // php artisan db:seed

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        /* User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */


        $this->call(StaffSeeder::class);

        SchoolYear::factory(1)->create();

        $this->call(GradeSectionSeeder::class);

        //GradeSection::factory(1)->create();

        Student::factory(10)->create();

        $this->call(ParentSeeder::class);
        $this->call(ReportSeeder::class);
    }
}
