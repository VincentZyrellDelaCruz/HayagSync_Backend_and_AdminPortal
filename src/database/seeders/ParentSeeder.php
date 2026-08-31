<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\ParentGuardian;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ParentSeeder extends Seeder
{
    public function run(): void
    {
        $staffUserIds = Staff::pluck('user_id')->toArray();
        $parentUsers = User::whereNotIn('id', $staffUserIds)->get();

        foreach ($parentUsers as $user) {
            $parent = ParentGuardian::create([
                'user_id' => $user->id,
                'parent_code' => strtoupper(Str::random(8)),
                'occupation' => fake()->jobTitle(),
            ]);

            $students = Student::inRandomOrder()->limit(rand(1, 3))->get();
            foreach ($students as $student) {
                $relationship = $student->gender === 'Male' ? 'Son' : 'Daughter';
                $parent->students()->attach($student->id, ['relationship' => $relationship]);
            }
        }
    }
}
