<?php

namespace Database\Seeders;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=ParentSeeder
     */
    public function run(): void
    {
        $user = User::orderBy('last_name', 'desc')->first()->id;
        $parent = ParentGuardian::create([
            'user_id' => $user,
            'parent_code' => strtoupper(Str::random(8)),
            'occupation' => 'Doctor',
        ]);
        // $parent_guardian = ParentGuardian::first();

        $students = Student::limit(2)->get();

        foreach ($students as $student) {
            $parent->students()->attach([
                $student->id => ['relationship' => 'Son'],
            ]);
        }
    }
}
