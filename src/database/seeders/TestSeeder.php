<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    // TESTING PURPOSES ONLY. USE WITH PRECAUTION
    // docker compose exec app php artisan db:seed --class=TestSeeder
    public function run(): void
    {
        // Just for demonstrating login OTP
        // UserLoginHistory::truncate();

        /* User::whereHas('staff.positions', function ($query) {
            $query->where('position_name', 'Principal');
        })->update([
            'email' => '' // Your real email here for actual mail testing
        ]); */

        /* Position::updateOrCreate(
            ['position_name' => 'Principal', 'department' => 'Administration'],
            ['position_name' => 'Principal', 'department' => 'Administration']
        );

        Position::updateOrCreate(
            ['position_name' => 'Ministrong Tagasubaybay', 'department' => 'Student Affairs'],
            ['position_name' => 'Ministrong Tagasubaybay', 'department' => 'Student Affairs']
        );

        Position::updateOrCreate(
            ['position_name' => 'OSD Officer', 'department' => 'OSD'],
            ['position_name' => 'OSD Officer', 'department' => 'OSD']
        );

        Position::updateOrCreate(
            ['position_name' => 'Teacher', 'department' => 'Junior High School'],
            ['position_name' => 'Teacher', 'department' => 'Junior High School']
        ); */
    }
}
