<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $positions = ['Teacher', 'Principal', 'Ministrong Tagasubaybay', 'OSD Officer'];
        foreach ($positions as $position) {
            Position::firstOrCreate(['position_name' => $position]);
        }

        $staffUsers = User::orderBy('last_name', 'asc')->limit(12)->get();

        foreach ($staffUsers as $user) {
            Staff::create([
                'user_id' => $user->id,
                'staff_number' => strtoupper(Str::random(8)),
            ]);
        }

        $staffs = Staff::orderBy('user_id')->get();

        $principal = $staffs->first();
        $principal->positions()->attach([
            Position::where('position_name', 'Principal')->first()->id => ['assigned_at' => now()],
        ]);

        $staffs->slice(1, 2)->each(function ($staff) {
            $staff->positions()->attach([
                Position::where('position_name', 'Ministrong Tagasubaybay')->first()->id => ['assigned_at' => now()],
            ]);
        });

        $staffs->slice(3, 2)->each(function ($staff) {
            $staff->positions()->attach([
                Position::where('position_name', 'OSD Officer')->first()->id => ['assigned_at' => now()],
            ]);
        });

        $staffs->slice(5)->each(function ($staff) {
            $staff->positions()->attach([
                Position::where('position_name', 'Teacher')->first()->id => ['assigned_at' => now()],
            ]);
        });

    }
}
