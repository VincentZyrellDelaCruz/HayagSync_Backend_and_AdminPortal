<?php

namespace Database\Seeders;

use App\Models\IncidentCategory;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Physical Bullying', 'Social Bullying', 'Verbal Bullying',
            'Cyberbullying', 'Religious Bullying', 'Sexual Bullying', 'Racial Bullying'];

        foreach ($categories as $category) {
            IncidentCategory::create([
                'category_name' => $category
            ]);
        }

        $statuses = ['Pending', 'Under Investigation' , 'Scheduled', 'Resolved', 'Dropped'];
        $sort_order = 1;

        foreach ($statuses as $status) {
            ReportStatus::create([
                'status_name' => $status,
                'sort_order' => $sort_order++,
            ]);
        }

        // ✅ Ensure we have a reporter
        $user = User::first() ?? User::factory()->create();

        // ✅ Create a report with required fields
        $report = Report::create([
            'reported_by'       => $user->id,
            'category_id'       => IncidentCategory::first()->id,
            'current_status_id' => ReportStatus::first()->id,
            'incident_title'    => 'Sample Incident',
            'description'       => 'This is a seeded report.',
            'location'          => 'School Grounds',
            'incident_date'     => now()->toDateString(),
            'incident_time'     => now()->toTimeString(),
        ]);

        $students = Student::limit(2)->get();

        $report->students()->attach([
            $students[0]->id => ['involvement_type' => 'Victim', 'notes' => 'Lorem Ipsum'],
            $students[1]->id => ['involvement_type' => 'Offender', 'notes' => null],
        ]);
    }
}
