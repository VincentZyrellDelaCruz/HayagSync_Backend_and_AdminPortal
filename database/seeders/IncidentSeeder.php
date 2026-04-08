<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentStatus;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=IncidentSeeder
     */
    public function run(): void
    {
        $categories = ['Physical Bullying', 'Social Bullying', 'Verbal Bullying',
                    'Cyberbullying', 'Religious Bullying', 'Sexual Bullying', 'Racial Bullying'];

        foreach ($categories as $category) {
            IncidentCategory::create([
                'category_name' => $category
            ]);
        }

        $statuses = ['Pending', 'Under Investigation' , 'Scheduled',
                    'Resolved', 'Unresolved', 'Cancelled'];
        $sort_order = 1;

        foreach ($statuses as $status) {
            IncidentStatus::create([
                'status_name' => $status,
                'sort_order' => $sort_order++,
            ]);
        }

        $incident = Incident::factory()->create();

        $students = Student::limit(2)->get();

        $incident->students()->attach([
            $students[0]->id => ['involvement_type' => 'Victim', 'notes' => 'Lorem Ipsum'],
            $students[1]->id => ['involvement_type' => 'Offender', 'notes' => null],
        ]);

    }
}
