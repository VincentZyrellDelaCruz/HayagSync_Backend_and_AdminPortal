<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentStatus;
use App\Models\IncidentUpdate;
use App\Models\Student;
use App\Models\User;
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
        /* $categories = ['Physical Bullying', 'Social Bullying', 'Verbal Bullying',
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
        ]); */

        /* Just to show if the IncidentUpdate table works
        $incident = Incident::find('http://127.0.0.1:8000/api/incidents/019da109-7c83-713a-af35-25a7562c7c23');
        $user = User::first()->id;
        $status = IncidentStatus::find(2)->id;

        IncidentUpdate::create([
            'incident_id' => $incident,
            'updated_by' => $user,
            'status_id' => $status,
            'note' => 'lol,'
        ]);
        */

    }
}
