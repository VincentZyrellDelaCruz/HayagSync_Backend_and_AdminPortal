<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Incident;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        /* User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */

        // Deletes all files, including subfolders
        File::cleanDirectory(storage_path('app/public'));

        User::factory(10)->create();

        School::factory(1)->create();

        Student::factory(10)->create();

        SchoolYear::factory(1)->create();

        Enrollment::factory(1)->create();

        $this->call(StaffSeeder::class);
        $this->call(ParentSeeder::class);
        $this->call(IncidentSeeder::class);
    }
}
