<?php

namespace App\Jobs;

use App\Events\ImportProgressUpdated;
use App\Models\DataImportBatch;
use App\Models\DataImportRow;
use App\Models\Enrollment;
use App\Models\GradeSection;
use App\Models\Position;
use App\Models\SchoolYear;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ProcessRosterImport implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1200;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $batchId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $batch = DataImportBatch::findOrFail($this->batchId);

        try {
            $batch->update([
                'status' => 'validating',
                'stage' => 'Reading and validating source file',
                'progress' => 5,
                'started_at' => now(),
            ]);

            $this->broadcastProgress($batch);

            $rows = $this->readRows(Storage::disk('local')->path($batch->file_path));

            $batch->update([
                'total_rows' => count($rows),
                'stage' => 'Validating records',
                'progress' => 10,
            ]);

            $this->broadcastProgress($batch);

            $this->validateAndStage($batch, $rows);

            if ($batch->refresh()->invalid_rows > 0) {
                $batch->update([
                    'status' => 'validation_failed',
                    'stage' => 'Validation Failed',
                    'progress' => 50,
                    'finished_at' => now()
                ]);

                $this->broadcastProgress($batch);

                return;
            }

            $batch->update([
                'status' => 'processing',
                'stage' => 'Applying validated changes',
                'progress' => 60,
            ]);

            $this->broadcastProgress($batch);

            $stats = [
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'deactivated' => 0,
            ];

            DB::transaction(function () use ($batch, &$stats) {
                $rows = DataImportRow::where('batch_id', $batch->id)
                    ->where('validation_status', 'valid')
                    ->orderBy('row_number')->get();

                $totalValidRows = $rows->count();

                $presentStudentNumbers = [];
                $presentStaffNumbers = [];

                foreach ($rows as $importRow) {
                    $result = $batch->import_type === 'students'
                        ? $this->processStudent($importRow)
                        : $this->processStaff($importRow);

                    $importRow->update([
                        'processing_status' => 'processed',
                        'action' => $result['action'],
                        'before_data' => $result['before'],
                        'after_data' => $result['after'],
                    ]);

                    $stats['processed']++;

                    if ($result['action'] === 'created') {
                        $stats['created']++;
                    }

                    if ($result['action'] === 'updated') {
                        $stats['updated']++;
                    }

                    if ($batch->import_type === 'students') {
                        $presentStudentNumbers[] = $importRow->identifier;
                    }
                    else {
                        $presentStaffNumbers[] = $importRow->identifier;
                    }

                    if ($totalValidRows > 0 && ($stats['processed'] % 10 === 0 || $stats['processed'] === $totalValidRows)) {
                        $processingProgress = 60 + (int) (($stats['processed'] / $totalValidRows) * 35);

                        $batch->update([
                            'processed_rows' => $stats['processed'],
                            'created_count' => $stats['created'],
                            'updated_count' => $stats['updated'],
                            'deactivated_count' => $stats['deactivated'],
                            'progress' => min($processingProgress, 95),
                            'stage' => 'Applying validated changes',
                        ]);

                        $this->broadcastProgress($batch);
                    }
                }

                if ($batch->mode === 'full_roster' && $batch->import_type === 'students') {
                    $stats['deactivated'] += $this->endMissingStudents($batch, $presentStudentNumbers, $rows);
                }

                if ($batch->mode === 'full_roster' && $batch->import_type === 'staff') {
                    $stats['deactivated'] += $this->deactivateMissingStaff($batch, $presentStaffNumbers);
                }
            });

            $batch->update([
                'status' => 'completed',
                'stage' => 'ETL completed successfully',
                'progress' => 100,
                'processed_rows' => $stats['processed'],
                'created_count' => $stats['created'],
                'updated_count' => $stats['updated'],
                'deactivated_count' => $stats['deactivated'],
                'finished_at' => now(),
            ]);

            $this->broadcastProgress($batch);

        } catch (Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'stage' => 'Processing failed! Database transaction rolled back',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            $this->broadcastProgress($batch);

            throw $e;
        }
    }

    private function readRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Import Data')
            ?? $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, true);

        if (empty($rawRows)) return [];

        $headerRow = array_shift($rawRows);

        $headers = array_map(fn($header) => Str::of((string) $header)
            ->trim()->snake()->toString(),
            $headerRow
        );

        $rows = [];

        foreach ($rawRows as $rawRow) {
            $row = [];

            foreach ($headers as $column => $header) {
                $row[$header] = isset($rawRow[$column])
                    ? trim((string) $rawRow[$column])
                    : null;
            }

            if (collect($row)->filter(
                fn($value) => $value !== null && $value !== ''
            )->isEmpty()) { continue; }

            $rows[] = $row;
        }

        return $rows;
    }

    private function validateAndStage(DataImportBatch $batch, array $rows): void
    {
        $seen = [];
        $valid = 0;
        $invalid = 0;
        $total = count($rows);

        foreach (array_values($rows) as $index => $row) {
            $identifier = $batch->import_type === 'students'
                ? trim((string) ($row['student_number'] ?? ''))
                : trim((string) ($row['staff_number'] ?? ''));

            $errors = [];

            // VALIDATION
            $rules = $batch->import_type === 'students'
                ? [
                    'student_number' => ['required', 'string', 'max:50'],
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'middle_name' => ['nullable', 'string', 'max:100'],
                    'suffix' => ['nullable', 'string', 'max:50'],
                    'gender' => ['required', 'in:Male,Female,Others'],
                    'birthdate' => ['required', 'date'],
                    'email' => ['required', 'email', 'max:255'],
                    'grade_level' => ['required', 'string', 'max:7'],
                    'section' => ['required', 'string', 'max:20'],
                    'school_year' => ['required', 'string', 'max:20'],
                    'enrollment_status' => ['required', 'in:Enrolled,Transferred,Dropped'],
                ]
                : [
                    'staff_number' => ['required', 'string', 'max:50'],
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'middle_name' => ['nullable', 'string', 'max:100'],
                    'suffix' => ['nullable', 'string', 'max:50'],
                    'gender' => ['required', 'in:Male,Female,Others'],
                    'birthdate' => ['required', 'date'],
                    'email' => ['required', 'email', 'max:255'],
                    'department' => ['required', 'string', 'max:255'],
                    'position_name' => ['required', 'string', 'max:255'],
                    'employment_status' => ['required', 'in:Active,Inactive'],
                ];

            $validator = Validator::make($row, $rules);

            $errors = $validator->errors()->all();

            if ($identifier === '') {
                $errors[] = 'Identifier is required!';
            }

            if(isset($seen[$identifier])) {
                $errors[] = 'Duplicate identifier found in the source file!';
            }

            $seen[$identifier] = true;

            if ($batch->import_type === 'students') {
                $schoolYear = SchoolYear::where(
                    'school_year', $row['school_year'] ?? ''
                )->first();

                if (!$schoolYear) {
                    $errors[] = 'School year does not exist!';
                }
                else {
                    $sectionExists = GradeSection::where(
                        'school_year_id', $schoolYear->id
                    )
                    ->where('grade_level', $row['grade_level'])
                    ->where('section', $row['section'])
                    ->exists();

                    if (!$sectionExists) {
                        $errors[] = 'Grade section does not exist for the specified school year!';
                    }
                }
            }
            else {
                $positionName = Str::lower(Str::squish((string) ($row['position_name'] ?? '')));
                $department = Str::lower(Str::squish((string) ($row['department'] ?? '')));

                $positionExists = Position::whereRaw(
                    'LOWER(TRIM(position_name)) = ?',
                    [$positionName]
                )->whereRaw(
                    'LOWER(TRIM(department)) = ?',
                    [$department]
                )->exists();

                if (!$positionExists) {
                    $errors[] = 'Position does not exist!';
                }
            }

            DataImportRow::create([
                'batch_id' => $batch->id,
                'row_number' => $index + 2,
                'identifier' => $identifier,
                'validation_status' => empty($errors) ? 'valid' : 'invalid',
                'processing_status' => 'pending',
                'raw_data' => $row,
                'errors' => empty($errors) ? null : $errors,
            ]);

            if (empty($errors)) {
                $valid++;
            } else {
                $invalid++;
            }

            if ($total > 0 && (($index + 1) % 25 === 0 || $index === $total - 1)) {
                $progress = 10 +
                    (int) ((($index + 1) / $total) * 40);

                $batch->update([
                    'valid_rows' => $valid,
                    'invalid_rows' => $invalid,
                    'progress' => $progress,
                ]);
            }
        }
    }

    private function processStudent(DataImportRow $importRow): array
    {
        $row = $importRow->raw_data;

        $student = Student::where('student_number', $row['student_number'])->first();

        $before = $student?->load('enrollments')->toArray();

        if (!$student) {
            $student = Student::create([
                'student_number' => $row['student_number'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'middle_name' => $row['middle_name'] ?? null,
                'suffix' => $row['suffix'] ?? null,
                'gender' => $row['gender'],
                'birthdate' => $row['birthdate'],
                'email' => $row['email'],
                'phone_number' => $row['phone_number'] ?? null,
                'status' => $row['enrollment_status'],
            ]);

            $action = 'created';
        } else {
            $student->update([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'middle_name' => $row['middle_name'] ?? null,
                'suffix' => $row['suffix'] ?? null,
                'gender' => $row['gender'],
                'birthdate' => $row['birthdate'],
                'email' => $row['email'],
                'phone_number' => $row['phone_number'] ?? null,
                'status' => $row['enrollment_status'],
            ]);

            $action = 'updated';
        }

        $schoolYear = SchoolYear::where('school_year',$row['school_year'])->firstOrFail();

        $gradeSection = GradeSection::where('school_year_id',$schoolYear->id)
            ->where('grade_level', $row['grade_level'])
            ->where('section', $row['section'])
            ->firstOrFail();

        $activeEnrollment = $student->enrollments()
            ->where('school_year_id', $schoolYear->id)
            ->whereNull('ended_at')
            ->latest('enrolled_at')
            ->latest('id')
            ->first();

        if ($activeEnrollment && (int) $activeEnrollment->grade_section_id !== (int) $gradeSection->id) {
            $activeEnrollment->update([
                'status' => 'Ended',
                'ended_at' => now(),
            ]);
        }

        if ($row['enrollment_status'] === 'Enrolled') {
            $enrollment = Enrollment::firstOrNew([
                'student_id' => $student->id,
                'grade_section_id' => $gradeSection->id,
                'school_year_id' => $schoolYear->id,
            ]);

            $enrollment->status = 'Enrolled';
            $enrollment->ended_at = null;
            $enrollment->enrolled_at ??= now()->toDateString();
            $enrollment->save();
        } elseif ($activeEnrollment) {
            $activeEnrollment->update([
                'status' => $row['enrollment_status'],
                'ended_at' => now(),
            ]);
        }

        $after = $student->fresh()->load('enrollments')->toArray();

        return compact('action', 'before', 'after');
    }

    private function processStaff(DataImportRow $importRow): array
    {
        $row = $importRow->raw_data;

        $staff = Staff::where('staff_number', $row['staff_number'])->first();

        $before = $staff?->load('user', 'positions')->toArray();

        if (!$staff) {
            $existingUser = User::where(
                'email',
                $row['email']
            )->first();

            if ($existingUser) {
                throw new \RuntimeException(
                    "Staff row {$row['staff_number']} conflicts with an existing non-staff user account."
                );
            }

            /* $user = User::create([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'middle_name' => $row['middle_name'] ?? null,
                'suffix' => $row['suffix'] ?? null,
                'birthdate' => $row['birthdate'],
                'email' => $row['email'],
                'password' => Hash::make(Str::random(48)),
                'phone_number' => $row['phone_number'] ?? null,
                'status' => 'Inactive',
            ]); */

            $user = new User();
            $user->first_name = $row['first_name'];
            $user->last_name = $row['last_name'];
            $user->middle_name = $row['middle_name'] ?? null;
            $user->suffix = $row['suffix'] ?? null;
            $user->gender = $row['gender'] ?? 'Others';
            $user->birthdate = $row['birthdate'];
            $user->email = $row['email'];
            $user->password = Hash::make(Str::random(48));
            $user->phone_number = $row['phone_number'] ?? null;
            $user->status = $row['employment_status'];
            $user->save();

            $staff = Staff::create([
                'user_id' => $user->id,
                'staff_number' => $row['staff_number'],
                'is_admin' => false,
            ]);

            $action = 'created';
        } else {
            $user = $staff->user;

            /* $user->update([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'middle_name' => $row['middle_name'] ?? null,
                'suffix' => $row['suffix'] ?? null,
                'birthdate' => $row['birthdate'],
                'email' => $row['email'],
                'phone_number' => $row['phone_number'] ?? null,
                'status' => $row['employment_status'],
            ]); */

            $user->first_name = $row['first_name'];
            $user->last_name = $row['last_name'];
            $user->middle_name = $row['middle_name'] ?? null;
            $user->suffix = $row['suffix'] ?? null;
            $user->gender = $row['gender'] ?? 'Others';
            $user->birthdate = $row['birthdate'];
            $user->email = $row['email'];
            $user->phone_number = $row['phone_number'] ?? null;
            $user->status = $row['employment_status'];
            $user->save();

            $action = 'updated';
        }

        $positionName = Str::lower(Str::squish((string) $row['position_name']));
        $department = Str::lower(Str::squish((string) $row['department']));

        $position = Position::whereRaw('LOWER(TRIM(position_name)) = ?', [$positionName])
            ->whereRaw('LOWER(TRIM(department)) = ?', [$department])->firstOrFail();

        $latestPosition = $staff->positions()
            ->orderByDesc('staff_position.assigned_at')
            ->first();

        if (!$latestPosition || (int) $latestPosition->id !== (int) $position->id) {
            $staff->positions()->attach($position->id, ['assigned_at' => now()]);
        }

        $after = $staff->fresh()->load('user', 'positions')->toArray();

        return compact('action','before','after');
    }

    private function endMissingStudents(DataImportBatch $batch, array $presentStudentNumbers, Collection $rows): int {
        $schoolYears = $rows->pluck('raw_data.school_year')->filter()->unique()->values();

        $count = 0;
        $generatedRowNumber = (int) $batch->total_rows;

        foreach ($schoolYears as $schoolYearName) {
            $schoolYear = SchoolYear::where(
                'school_year',
                $schoolYearName
            )->first();

            if (!$schoolYear) continue;

            $enrollments = Enrollment::with('student')
                ->where('school_year_id', $schoolYear->id)
                ->whereNull('ended_at')
                ->whereHas(
                    'student',
                    function ($query) use ($presentStudentNumbers) {
                        $query->whereNotIn(
                            'student_number',
                            $presentStudentNumbers
                        );
                    }
                )
                ->get();

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $before = $student?->fresh()->load('enrollments')->toArray();

                $enrollment->update([
                    'status' => 'Ended',
                    'ended_at' => now(),
                ]);

                if ($student) {
                    $hasActiveEnrollment = $student->enrollments()
                        ->whereNull('ended_at')
                        ->exists();

                    if (!$hasActiveEnrollment) {
                        $student->update([
                            'status' => 'Inactive',
                        ]);
                    }
                }

                $after = $student?->fresh()->load('enrollments')->toArray();

                if ($student) {
                    DataImportRow::create([
                        'batch_id' => $batch->id,
                        'row_number' => ++$generatedRowNumber,
                        'identifier' => $student->student_number,
                        'validation_status' => 'valid',
                        'processing_status' => 'processed',
                        'action' => 'deactivated',
                        'raw_data' => [
                            'student_number' => $student->student_number,
                            'school_year' => $schoolYearName,
                            'action_source' => 'full_roster',
                            'reason' => 'Student was not present in the authoritative roster.',
                        ],
                        'errors' => null,
                        'before_data' => $before,
                        'after_data' => $after,
                    ]);
                }

                $count++;
            }
        }

        return $count;
    }

    private function deactivateMissingStaff(DataImportBatch $batch, array $presentStaffNumbers): int {
        $count = 0;
        $generatedRowNumber = (int) $batch->total_rows;

        Staff::with('user')->where('is_admin', false)->whereNotIn('staff_number', $presentStaffNumbers)
            ->get()
            ->each(function ($staff) use ($batch, &$count, &$generatedRowNumber) {
                if ($staff->user && $staff->user->status !== 'Inactive') {
                    $before = $staff->fresh()->load('user', 'positions')->toArray();

                    $staff->user->update([
                        'status' => 'Inactive',
                    ]);

                    $after = $staff->fresh()->load('user', 'positions')->toArray();

                    DataImportRow::create([
                        'batch_id' => $batch->id,
                        'row_number' => ++$generatedRowNumber,
                        'identifier' => $staff->staff_number,
                        'validation_status' => 'valid',
                        'processing_status' => 'processed',
                        'action' => 'deactivated',
                        'raw_data' => [
                            'staff_number' => $staff->staff_number,
                            'action_source' => 'full_roster',
                            'reason' => 'Staff member was not present in the authoritative roster.',
                        ],
                        'errors' => null,
                        'before_data' => $before,
                        'after_data' => $after,
                    ]);

                    $count++;
                }
            });

        return $count;
    }

    private function broadcastProgress(DataImportBatch $batch, ?int $progress = null, ?string $stage = null): void
    {
        $batch->refresh();

        $payload = [
            'id' => (string) $batch->id,
            'initiated_by' => (string) $batch->initiated_by,
            'import_type' => $batch->import_type,
            'mode' => $batch->mode,
            'status' => $batch->status,
            'stage' => $stage ?? $batch->stage,
            'progress' => $progress ?? (int) $batch->progress,
            'total_rows' => (int) $batch->total_rows,
            'valid_rows' => (int) $batch->valid_rows,
            'invalid_rows' => (int) $batch->invalid_rows,
            'processed_rows' => (int) $batch->processed_rows,
            'created_count' => (int) $batch->created_count,
            'updated_count' => (int) $batch->updated_count,
            'deactivated_count' => (int) $batch->deactivated_count,
            'error_message' => $batch->error_message,
            'started_at' => $batch->started_at?->toIso8601String(),
            'finished_at' => $batch->finished_at?->toIso8601String(),
        ];

        ImportProgressUpdated::dispatch($payload);
    }
}
