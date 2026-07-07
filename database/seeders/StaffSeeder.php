<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('HayagSync2026!');

        // Preload 12 Section Advisers matching Bolt.new section names and emails
        $advisers = [
            ['name' => 'Ms. Clara Mahogany', 'email' => 'adviser.grade-7-mahogany@hayag.edu.ph', 'assigned_section' => 'Grade 7 - Mahogany'],
            ['name' => 'Mr. Dennis Narra', 'email' => 'adviser.grade-7-narra@hayag.edu.ph', 'assigned_section' => 'Grade 7 - Narra'],
            ['name' => 'Ms. Elena Uranus', 'email' => 'adviser.grade-8-uranus@hayag.edu.ph', 'assigned_section' => 'Grade 8 - Uranus'],
            ['name' => 'Mr. Felix Venus', 'email' => 'adviser.grade-8-venus@hayag.edu.ph', 'assigned_section' => 'Grade 8 - Venus'],
            ['name' => 'Ms. Grace Eagle', 'email' => 'adviser.grade-9-eagle@hayag.edu.ph', 'assigned_section' => 'Grade 9 - Eagle'],
            ['name' => 'Mr. Henry Falcon', 'email' => 'adviser.grade-9-falcon@hayag.edu.ph', 'assigned_section' => 'Grade 9 - Falcon'],
            ['name' => 'Ms. Iris Rizal', 'email' => 'adviser.grade-10-rizal@hayag.edu.ph', 'assigned_section' => 'Grade 10 - Rizal'],
            ['name' => 'Mr. Jack Bonifacio', 'email' => 'adviser.grade-10-bonifacio@hayag.edu.ph', 'assigned_section' => 'Grade 10 - Bonifacio'],
            ['name' => 'Ms. Julia Jupiter', 'email' => 'adviser.grade-10-jupiter@hayag.edu.ph', 'assigned_section' => 'Grade 10 - Jupiter'],
            ['name' => 'Ms. Karen Stem', 'email' => 'adviser.grade-11-stem-a@hayag.edu.ph', 'assigned_section' => 'Grade 11 - STEM A'],
            ['name' => 'Mr. Leo Abm', 'email' => 'adviser.grade-11-abm-a@hayag.edu.ph', 'assigned_section' => 'Grade 11 - ABM A'],
            ['name' => 'Ms. Maria Stem12', 'email' => 'adviser.grade-12-stem-a@hayag.edu.ph', 'assigned_section' => 'Grade 12 - STEM A'],
            ['name' => 'Mr. Nilo Abm12', 'email' => 'adviser.grade-12-abm-a@hayag.edu.ph', 'assigned_section' => 'Grade 12 - ABM A'],
        ];

        foreach ($advisers as $adv) {
            User::create([
                'name' => $adv['name'],
                'email' => $adv['email'],
                'password' => $password,
                'role' => 'adviser',
                'assigned_section' => $adv['assigned_section'],
            ]);
        }

        // Preload Principal
        User::create([
            'name' => 'Principal Principal',
            'email' => 'principal@hayag.edu.ph',
            'password' => $password,
            'role' => 'principal',
        ]);

        // Preload Ministrong Tagasubaybay
        User::create([
            'name' => 'Ministrong Tagasubaybay',
            'email' => 'ministrong@hayag.edu.ph',
            'password' => $password,
            'role' => 'ministrong_tagasubaybay',
        ]);

        // Preload OSD Officer
        User::create([
            'name' => 'OSD Officer',
            'email' => 'osd@hayag.edu.ph',
            'password' => $password,
            'role' => 'osd',
        ]);

    }
}

