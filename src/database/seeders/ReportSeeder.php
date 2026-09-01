<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\IncidentCategory;
use App\Models\ReportEvidence;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->deleteDirectory('evidences');
        Storage::disk('public')->makeDirectory('evidences/images');
        Storage::disk('public')->makeDirectory('evidences/videos');

        $categories = [
            'Physical Bullying', 'Social Bullying', 'Verbal Bullying',
            'Cyberbullying', 'Religious Bullying', 'Sexual Bullying', 'Racial Bullying'
        ];

        foreach ($categories as $category) {
            IncidentCategory::firstOrCreate(['category_name' => $category]);
        }

        $statuses = ['Pending', 'Under Investigation', 'Scheduled', 'Escalated', 'Resolved', 'Dismissed'];
        $sort_order = 1;

        foreach ($statuses as $status) {
            ReportStatus::firstOrCreate([
                'status_name' => $status,
                'sort_order' => $sort_order++,
            ]);
        }

        $user = User::first() ?? User::factory()->create();

        $year = now()->year;
        $lastCode = Report::whereYear('created_at', $year)
            ->orderBy('report_code', 'desc')
            ->value('report_code');
        $counter = $lastCode ? (int) substr($lastCode, 9) : 0;

        for ($i = 1; $i <= 20; $i++) {
            $counter++;
            $reportCode = "BIR-{$year}-" . str_pad($counter, 6, '0', STR_PAD_LEFT);

            $report = Report::create([
                'report_code'       => $reportCode,
                'reported_by'       => $user->id,
                'category_id'       => IncidentCategory::inRandomOrder()->first()->id,
                'current_status_id' => 1,
                'incident_title'    => "Incident #{$i}",
                'description'       => fake()->paragraph(3),
                'location'          => fake()->randomElement(['Classroom', 'Hallway', 'Playground', 'Cafeteria']),
                'incident_date'     => now()->subDays(rand(0, 30))->toDateString(),
                'incident_time'     => now()->subHours(rand(1, 12))->toTimeString(),
            ]);

            $students = Student::inRandomOrder()->limit(rand(2, 5))->get();

            if ($students->count() >= 2) {
                $report->students()->attach([
                    $students[0]->id => ['involvement_type' => 'Victim', 'notes' => fake()->sentence()],
                    $students[1]->id => ['involvement_type' => 'Offender', 'notes' => fake()->sentence()],
                ]);
            }

            // Optionally add witnesses
            if ($students->count() > 2) {
                foreach ($students->slice(2) as $witness) {
                    $report->students()->attach([
                        $witness->id => ['involvement_type' => 'Witness', 'notes' => fake()->sentence()],
                    ]);
                }
            }

            if (rand(0, 1)) {
                $disk = Storage::disk('test_file');

                $images = collect($disk->files('images'))->filter(fn($f) => Str::endsWith($f, ['.jpg', '.jpeg', '.png']));
                $videos = collect($disk->files('videos'))->filter(fn($f) => Str::endsWith($f, ['.mp4']));

                $chosenType = fake()->randomElement(['image', 'video']);
                $chosenFile = $chosenType === 'image' ? ($images->count() ? $images->random() : null)
                                        : ($videos->count() ? $videos->random() : null);

                if ($chosenFile) {
                    $filename = basename($chosenFile);
                    $newPath  = 'evidences/' . ($chosenType === 'image' ? 'images/' : 'videos/') . Str::uuid() . '_' . $filename;

                    // Stream copy instead of loading whole file into memory.
                    // Prevents silent empty-file writes on large videos when
                    // file_get_contents() hits PHP's memory_limit.
                    $stream = $disk->readStream($chosenFile);

                    if ($stream !== null && is_resource($stream)) {
                        Storage::disk('public')->writeStream($newPath, $stream);

                        if (is_resource($stream)) {
                            fclose($stream);
                        }

                        $mimeType = $chosenType === 'image' ? 'image/jpeg' : 'video/mp4';

                        ReportEvidence::create([
                            'report_id' => $report->id,
                            'uploaded_by' => $user->id,
                            'file_name' => $filename,
                            'file_path' => $newPath,
                            'file_type' => $chosenType,
                            'file_size' => Storage::disk('public')->size($newPath),
                            'mime_type' => $mimeType,
                            'caption' => fake()->sentence(),
                            'hash_signature' => Str::uuid(),
                        ]);
                    } else {
                        $this->command?->warn("Could not open stream for: {$chosenFile}");
                    }
                }
            }
        }
    }
}
