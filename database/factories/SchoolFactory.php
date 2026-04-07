<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_code' => str_pad(fake()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'school_name' => 'New Era University Integrated School',
            'street_address' => '9 Central Avenue',
            'barangay' => 'New Era',
            'city' => 'Quezon City',
            'contact_number' => '0345 811 8111',
            'email_address' => 'inquiries@neu.edu.ph',
            'website_url' => 'https://neu.edu.ph',
        ];
    }
}
