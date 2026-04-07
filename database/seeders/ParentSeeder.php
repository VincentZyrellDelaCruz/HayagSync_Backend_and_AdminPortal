<?php

namespace Database\Seeders;

use App\Models\ParentGuardian;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=ParentSeeder
     */
    public function run(): void
    {
        ParentGuardian::factory()->create();
        $parent_guardian = ParentGuardian::first();

        $students = Student::limit(2)->get();

        foreach ($students as $student) {
            $parent_guardian->students()->attach([
                $student->id => ['relationship' => 'Father'],
            ]);
        }
    }
}
