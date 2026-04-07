<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use function Symfony\Component\Clock\now;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=StaffSeeder
     */
    public function run(): void
    {
        $users = User::oldest()->limit(9)->get();

        foreach ($users as $user) {
            Staff::factory()->create([
                'user_id' => $user->id,
                'staff_number' => fake()->numberBetween(10, 20) . str_pad(fake()->unique()->numberBetween(0, 9999), 4, '0', STR_PAD_LEFT),
                'department' => 'Office of Student Discipline',
                'school_id' => School::first()->id,
            ]);
        }

        $positions = ['Director', 'Assistant Director', 'Coordinator', 'Officer', 'Admin'];

        foreach ($positions as $position) {
            Position::create([
                'position_name' => $position,
            ]);
        }

        $staffs = Staff::all();

        foreach ($staffs as $staff) {
            $staff->positions()->attach([
                Position::inRandomOrder()->first()->id => ['assigned_at' => now()],
            ]);
        }
    }
}
