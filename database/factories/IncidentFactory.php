<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentStatus;
use App\Models\ParentGuardian;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::first()->id,
            'reported_by' => ParentGuardian::first()->user_id,
            'category_id' => IncidentCategory::inRandomOrder()->first()->id,
            'current_status_id' => IncidentStatus::first()->id,
            'incident_title' => 'Test bully title for my son',
            'description' => 'Never Gonna Give You Up',
            'incident_datetime' => fake()->dateTimeThisMonth()->format('Y-m-d H:i:s'),
            'location' => 'Room 414',
            'latitude' => 14.6634,
            'longitude' => 121.0573,
        ];
    }
}
