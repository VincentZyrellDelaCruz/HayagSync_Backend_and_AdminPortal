<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Jobs\AssessReportUrgency;
use App\Models\ActivityLog;
use App\Models\DisciplinaryAction;
use App\Models\IncidentCategory;
use App\Models\MeetingParticipant;
use App\Models\Report;
use App\Models\ReportAssignment;
use App\Models\ReportEvidence;
use App\Models\ReportStatus;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ReportEvidenceVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $req)
    {
        $authUser = Auth::user();

        if ($authUser?->parent_guardian && !$authUser->staff) {
            return $this->parentIndex($req);
        }

        $selectedStatus = $req->query('status', 'all');
        $selectedCategory = $req->query('category', 'all');

        $categories = Cache::remember('reports:active_categories', now()->addMinutes(5), fn () =>
            IncidentCategory::where('is_active', true)->orderBy('category_name')->get()
        );

        $authStaff = $authUser?->staff;

        abort_unless($authStaff, 403, 'Staff access required.');

        $isAdmin = (bool) $authStaff->is_admin;
        $staffLevel = $this->staffLevel($authStaff);

        $reportsQuery = Report::with([
            'user',
            'current_status',
            'category',
            'latest_assignment.staff_assigned_to.user',
        ]);

        if (!$isAdmin) {
            $reportsQuery->where(function ($query) use ($authStaff, $staffLevel) {
                $query->whereHas('report_assignments', fn ($assignmentQuery) =>
                    $assignmentQuery->where('assigned_to', $authStaff->getKey())
                );

                if ($staffLevel === 2) {
                    $query->orWhere(function ($levelQuery) {
                        $levelQuery
                            ->whereHas('latest_assignment', fn ($assignmentQuery) => $assignmentQuery->where('level', '1'))
                            ->orWhere(function ($pendingQuery) {
                                $pendingQuery
                                    ->whereHas('current_status', fn ($statusQuery) => $statusQuery->where('status_name', 'Pending'))
                                    ->whereDoesntHave('latest_assignment');
                            });
                    });
                }

                if ($staffLevel === 1) {
                    $query->orWhere(function ($teacherQuery) use ($authStaff) {
                        $teacherQuery
                            ->whereHas('current_status', fn ($statusQuery) => $statusQuery->where('status_name', 'Pending'))
                            ->whereDoesntHave('latest_assignment', fn ($assignmentQuery) => $assignmentQuery->where('level', '1'))
                            ->whereHas('students', function ($studentQuery) use ($authStaff) {
                                $studentQuery
                                    ->whereIn('report_student.involvement_type', ['victim', 'Victim', 'target', 'Target'])
                                    ->whereHas('grade_sections', fn ($sectionQuery) =>
                                        $sectionQuery
                                            ->where('adviser', $authStaff->getKey())
                                            ->whereNull('enrollments.ended_at')
                                    );
                            });
                    });
                }
            });
        }

        if ($selectedStatus !== 'all') {
            $reportsQuery->whereHas('current_status', fn ($query) => $query->where('status_name', $selectedStatus));
        }

        if ($selectedCategory !== 'all') {
            $reportsQuery->where('category_id', $selectedCategory);
        }

        $reports = $reportsQuery->latest('created_at')->paginate(10)->withQueryString();

        $reports->getCollection()->transform(function ($report) use ($authUser) {
            $assignment = $report->latest_assignment;
            $report->current_level = $assignment ? (int) $assignment->level : null;
            $report->current_assignee = $assignment?->staff_assigned_to;
            $report->can_act = $this->canActOnReport($authUser, $report);
            $report->read_only = !$report->can_act;

            return $report;
        });

        $statuses = ['all', 'Pending', 'Under Investigation', 'Scheduled', 'Escalated', 'Resolved', 'Dismissed'];

        return Inertia::render('Reports/Index', compact(
            'reports',
            'categories',
            'statuses',
            'selectedStatus',
            'selectedCategory'
        ));
    }

    private function parentIndex(Request $req)
    {
        $user = Auth::user();

        $reports = Report::query()
            ->where('reported_by', $user->id)
            ->with([
                'category:id,category_name',
                'current_status:id,status_name',
                'students:id,first_name,last_name,middle_name,suffix,student_number,gender,email,phone_number',
            ])
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $reports->getCollection()->transform(function ($report) {
            $victim = $report->students->first(fn ($student) =>
                in_array(
                    strtolower((string) $student->pivot->involvement_type),
                    ['victim', 'target'],
                    true
                )
            );

            $report->parent_student = $victim ? [
                'id' => $victim->id,
                'name' => trim("{$victim->first_name} {$victim->last_name}"),
                'student_number' => $victim->student_number,
            ] : null;

            return $report;
        });

        return Inertia::render('Reports/ParentIndex', compact('reports'));
    }

    public function create()
    {
        $user = Auth::user();

        abort_unless($user?->parent_guardian && !$user->staff, 403,
            'Parent/guardian access required.'
        );

        $categories = IncidentCategory::query()->where('is_active', true)
            ->whereNull('deleted_at')->orderBy('category_name')
            ->get(['id', 'category_name', 'description']);

        $relatedStudents = $user->parent_guardian->students()
            ->whereHas('enrollments', function ($query) {
                $query->where('status', 'Enrolled')->whereNull('ended_at');
            })
            ->with(['activeEnrollment.grade_section'])
            ->orderBy('last_name')->orderBy('first_name')->get()
            ->map(function ($student) {
                $enrollment = $student->activeEnrollment;

                return [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'middle_name' => $student->middle_name,
                    'suffix' => $student->suffix,
                    'grade_level' => $enrollment?->grade_section?->grade_level,
                    'section' => $enrollment?->grade_section?->section,
                ];
            })
            ->values();

        return Inertia::render('Reports/Create', [
            'categories' => $categories,
            'relatedStudents' => $relatedStudents,
        ]);
    }

    public function studentSearch(Request $req): JsonResponse
    {
        $user = Auth::user();

        abort_unless(
            $user?->parent_guardian && !$user->staff,
            403,
            'Parent/guardian access required.'
        );

        $validated = $req->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $search = trim($validated['q']);

        $students = Student::query()
            ->with(['latestEnrollment.grade_section'])
            ->where(function ($query) use ($search) {
                $query
                    ->where('student_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->whereHas(
                'latestEnrollment',
                fn ($query) => $query->where('status', 'Enrolled')->whereNull('ended_at')
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get()
            ->map(fn ($student) => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'grade_level' => $student->latestEnrollment?->grade_section?->grade_level,
                'section' => $student->latestEnrollment?->grade_section?->section,
            ])
            ->values();

        return response()->json(['students' => $students]);
    }

    public function store(ReportRequest $req, ReportEvidenceVerificationService $evidenceVerificationService)
    {
        $user = Auth::user();
        $validated = $req->validated();

        abort_unless($user?->parent_guardian && !$user->staff, 403, 'Parent/guardian access required.');

        $validated = $req->validated();

        $report = DB::transaction(function () use ($validated, $user, $req, $evidenceVerificationService) {
            $pendingStatus = ReportStatus::where('status_name', 'Pending')->firstOrFail();

            $report = Report::create([
                /* 'report_code' => $this->generateReportCode(), */
                'reported_by' => $user->id,
                'category_id' => $validated['category_id'],
                'current_status_id' => $pendingStatus->id,
                'incident_title' => trim($validated['incident_title']),
                'description' => trim($validated['description']),
                'location' => filled($validated['location'] ?? null)
                    ? trim($validated['location'])
                    : null,
                'incident_date' => $validated['incident_date'],
                'incident_time' => $validated['incident_time'] ?? null,
                'urgent_safety_flag' => null,
                'urgency_assessed_at' => null,
            ]);

            $pivotData = [];

            foreach ($validated['victim_ids'] as $studentId) {
                $pivotData[$studentId] = ['involvement_type' => 'Victim'];
            }

            foreach ($validated['offender_ids'] ?? [] as $studentId) {
                $pivotData[$studentId] = ['involvement_type' => 'Offender'];
            }

            foreach ($validated['witness_ids'] ?? [] as $studentId) {
                $pivotData[$studentId] = ['involvement_type' => 'Witness'];
            }

            $report->students()->attach($pivotData);

            foreach ($validated['evidence'] ?? [] as $evidenceItem) {
                $file = $evidenceItem['file'];
                $mimeType = $file->getMimeType() ?? 'application/octet-stream';

                $fileType = match (true) {
                    str_starts_with($mimeType, 'image/') => 'image',
                    str_starts_with($mimeType, 'video/') => 'video',
                    str_starts_with($mimeType, 'audio/') => 'audio',
                    default => 'other',
                };

                $storedName = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
                $path = $file->storeAs("evidences/{$fileType}", $storedName, 'public');
                $hash = hash_file('sha256', $file->getRealPath());

                $verification = $evidenceVerificationService->verify($file);

                $report->report_evidences()->create([
                    'uploaded_by' => $user->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $fileType,
                    'file_size' => $file->getSize(),
                    'mime_type' => $mimeType,
                    'caption' => $evidenceItem['caption'] ?? null,
                    'hash_signature' => $hash,
                    'evidence_verification_state' => $verification['state'],
                    'evidence_verification_details' => $verification['details'],
                ]);
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'action_type' => 'report_submitted',
                'description' => sprintf('Parent/guardian submitted incident report %s.', $report->report_code),
                'module' => 'reports',
                'record_id' => $report->id,
                'ip_address' => $req->ip(),
            ]);

            return $report;
        });

        AssessReportUrgency::dispatch(
            $report->id,
            $req->ip() ?: '0.0.0.0'
        )->afterCommit();

        return redirect()->route('web.reports.index')
            ->with('success', 'Your incident report was submitted successfully. The school will review it and determine the appropriate handling.');
    }

    // AUTOMATICALLY GENERATES VERY FIRST REPORT CODE, NOT FOR FREQUENT USE
    /* private function generateFirstReportCode(): string
    {
        $year = now()->year;
        $code = 'BIR-' . $year . '-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return $code;
    } */

    public function show(string $id)
    {
        $authUser = Auth::user();
        $report = Report::with(['current_status', 'latest_assignment'])->findOrFail($id);

        if ($authUser?->parent_guardian && !$authUser->staff) {
            abort_unless(
                $this->canViewReport($authUser, $report), 403,
                'You are not authorized to view this incident.'
            );

            $report->load([
                'user',
                'category',
                'current_status',
                'students:id,first_name,last_name,middle_name,suffix,student_number,gender,email,phone_number',
                'report_evidences',
            ]);

            $report->students->each(function ($student) {
                $student->pivot->notes = null;
            });

            $report->current_level = null;
            $report->current_assignee = null;
            $report->can_act = false;
            $report->read_only = true;
            $report->setRelation('report_updates', collect());
            $report->setRelation('meetings', collect());
            $report->setRelation('latest_assignment', null);

            return Inertia::render('Reports/Report', [
                'report' => $report,
                'statuses' => collect(),
            ]);
        }

        $this->ensureInitialAssignment($report);

        abort_unless($this->canViewReport(Auth::user(), $report), 403, 'You are not authorized to view this incident.');

        $report->load([
            'user',
            'category',
            'current_status',
            'students',
            'report_evidences',
            'latest_update.report_status',
            'latest_update.user.staff',
            'report_updates' => fn ($query) => $query->with(['report_status', 'user.staff'])->latest(),
            'meetings.scheduler',
            'meetings.participants.student',
            'meetings.participants.user',
            'latest_assignment.staff_assigned_to.user',
            'report_assignments.staff_assigned_to.user',
            'report_assignments.staff_assigned_by.user',
        ]);

        $assignment = $report->latest_assignment;

        $report->current_level = $assignment ? (int) $assignment->level : null;
        $report->current_assignee = $assignment?->staff_assigned_to;
        $report->can_act = $this->canActOnReport(Auth::user(), $report);
        $report->read_only = !$report->can_act;

        $statuses = Cache::remember('reports:action_statuses',
            now()->addMinutes(5),
            fn () => ReportStatus::whereIn(
                'status_name', ['Scheduled', 'Escalated', 'Resolved', 'Dismissed']
            )->get()
        );

        return Inertia::render('Reports/Report', compact('report', 'statuses'));
    }

    public function streamEvidence(string $id)
    {
        $evidence = ReportEvidence::with('report')->findOrFail($id);
        $user = Auth::user();

        $isStaff = (bool) $user?->staff;
        $isParentOwner = $user?->parent_guardian && (string) $evidence->report?->reported_by === (string) $user->id;

        abort_unless($isStaff || $isParentOwner, 403, 'You are not authorized to access this evidence.');

        $path = Storage::disk('public')->path($evidence->file_path);

        abort_unless(is_file($path), 404);

        $mimeType = $evidence->mime_type ?: Storage::disk('public')->mimeType($evidence->file_path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($evidence->file_name) . '"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function forward(Request $req, string $id)
    {
        $validated = $req->validate(['note' => 'required|string|min:5|max:2000']);

        $report = Report::with(['current_status', 'latest_assignment'])->findOrFail($id);

        $this->authorizeCurrentHandler($report, $report->latest_assignment);

        $currentLevel = (int) ($report->latest_assignment?->level ?? 0);

        abort_if($currentLevel < 1 || $currentLevel >= 4, 422, 'This incident cannot be escalated further.');

        $nextLevel = $currentLevel + 1;
        $nextStaff = $this->findStaffForLevel($nextLevel);

        abort_unless($nextStaff, 422, 'No eligible staff member is available for the next escalation level.');

        $escalatedStatus = ReportStatus::where('status_name', 'Escalated')->firstOrFail();

        $staff = Auth::user()->staff;

        DB::transaction(function () use ($report, $nextStaff, $nextLevel, $validated, $escalatedStatus, $staff) {
            $currentAssignment = $report->latest_assignment;

            $now = now();

            $currentAssignment->update(['ended_at' => $now]);

            $report->report_assignments()->create([
                'assigned_to' => $nextStaff->getKey(),
                'assigned_by' => $staff->getKey(),
                'level' => (string) $nextLevel,
                'assigned_at' => $now,
                'ended_at' => null,
            ]);

            $report->update(['current_status_id' => $escalatedStatus->id]);

            $report->report_updates()->create([
                'updated_by' => Auth::user()->id,
                'status_id' => $escalatedStatus->id,
                'note' => $validated['note'],
            ]);

            ActivityLog::create([
                'user_id' => Auth::user()->id,
                'action_type' => 'report_escalated',
                'description' => sprintf('Escalated report %s from level %s to level %s.', $report->report_code ?? $report->id, $currentAssignment->level, $nextLevel),
                'module' => 'reports',
                'record_id' => $report->id,
                'ip_address' => request()->ip(),
            ]);
        });

        // NOTIFICATION
        NotificationService::send(
            receiver: $nextStaff->user,
            type: 'report_escalated',
            title: 'Incident Report Escalated',
            message: sprintf(
                'Incident report %s has been escalated to your level for review.',
                $report->report_code ?? $report->id
            ),
            actionUrl: route(
                'web.reports.show',
                $report->id
            ),
            sender: $staff->user,
            priority: 'high',
            data: [
                'report_id' => (string) $report->id,
                'report_code' => $report->report_code ?? null,
                'escalation_level' => $nextLevel,
            ]
        );

        return back()->with('success', 'Incident report escalated successfully.');
    }

    public function resolve(Request $req, string $id)
    {
        $report = Report::with(['current_status', 'latest_assignment', 'students'])->findOrFail($id);

        $this->authorizeCurrentHandler($report, $report->latest_assignment);

        $currentLevel = (int) ($report->latest_assignment?->level ?? 0);

        $validated = $req->validate([
            'resolution_note' => 'required|string|min:5|max:2000',
            'offender_id' => $currentLevel === 4 ? 'required|uuid|exists:students,id' : 'nullable|uuid|exists:students,id',
            'discipline_action' => $currentLevel === 4 ? 'required|string|max:255' : 'nullable|string|max:255',
            'discipline_notes' => 'nullable|string|max:2000',
        ]);

        $offender = null;

        if ($currentLevel === 4) {
            $offender = $report->students()
                ->where('students.id', $validated['offender_id'])
                ->wherePivotIn('involvement_type', ['offender', 'Offender'])
                ->first();

            abort_unless($offender, 422, 'The selected student is not recorded as a confirmed offender for this report.');
        }

        $resolvedStatus = ReportStatus::where('status_name', 'Resolved')->firstOrFail();

        $staff = Auth::user()->staff;

        DB::transaction(function () use ($report, $resolvedStatus, $validated, $offender, $currentLevel, $staff) {
            $report->update(['current_status_id' => $resolvedStatus->id]);

            if ($offender && $currentLevel === 4) {
                DisciplinaryAction::create([
                    'report_id' => $report->id,
                    'student_id' => $offender->id,
                    'staff_id' => $staff->getKey(),
                    'discipline_action' => $validated['discipline_action'],
                    'notes' => $validated['discipline_notes'] ?? null,
                ]);
            }

            $report->latest_assignment()->update(['ended_at' => now()]);

            $report->report_updates()->create([
                'updated_by' => Auth::user()->id,
                'status_id' => $resolvedStatus->id,
                'note' => $validated['resolution_note'],
            ]);

            ActivityLog::create([
                'user_id' => Auth::user()->id,
                'action_type' => 'report_resolved',
                'description' => sprintf('Resolved report %s at escalation level %s.', $report->report_code ?? $report->id, $currentLevel),
                'module' => 'reports',
                'record_id' => $report->id,
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', 'Incident report resolved successfully.');
    }

    public function dismiss(Request $req, string $id)
    {
        $validated = $req->validate([
            'dismissal_note' => 'required|string|min:10|max:2000',
        ]);

        $report = Report::with(['current_status', 'latest_assignment'])->findOrFail($id);

        $this->authorizeCurrentHandler($report, $report->latest_assignment);

        $dismissedStatus = ReportStatus::where('status_name', 'Dismissed')->firstOrFail();

        DB::transaction(function () use ($report, $dismissedStatus, $validated) {
            $report->update(['current_status_id' => $dismissedStatus->id]);

            $report->latest_assignment()->update(['ended_at' => now()]);

            $report->report_updates()->create([
                'updated_by' => Auth::user()->id,
                'status_id' => $dismissedStatus->id,
                'note' => $validated['dismissal_note'],
            ]);

            ActivityLog::create([
                'user_id' => Auth::user()->id,
                'action_type' => 'report_dismissed',
                'description' => sprintf('Dismissed report %s.', $report->report_code ?? $report->id),
                'module' => 'reports',
                'record_id' => $report->id,
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', 'Incident report dismissed successfully.');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $req, string $id)
    {
        $validated = $req->validate([
            'meeting_datetime' => 'required|date|after:now',
            'meeting_type' => 'required|in:In-Person,Virtual,Both',
            'purpose' => 'required|string|max:2000',
            'note' => 'nullable|string|max:2000',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'uuid|exists:students,id',
            'guest_name' => 'nullable|string|max:255',
            'type' => 'required|in:schedule',
        ]);

        $report = Report::with('latest_assignment')->findOrFail($id);

        $this->authorizeCurrentHandler($report, $report->latest_assignment);

        DB::transaction(function () use ($report, $validated) {
            $scheduledStatus = ReportStatus::where('status_name', 'Scheduled')->firstOrFail();

            $meeting = $report->meetings()->create([
                'scheduled_by' => Auth::user()->id,
                'meeting_date' => $validated['meeting_datetime'],
                'meeting_type' => $validated['meeting_type'],
                'purpose' => $validated['purpose'],
                'notes' => $validated['note'] ?? null,
                'status' => 'Active',
            ]);

            foreach ($validated['participant_ids'] ?? [] as $studentId) {
                $student = $report->students()->where('students.id', $studentId)->first();

                if (!$student) {
                    continue;
                }

                MeetingParticipant::create([
                    'meeting_id' => $meeting->id,
                    'student_id' => $student->id,
                    'participant_role' => $student->pivot->involvement_type ?? 'Participant',
                    'attendance_status' => null,
                ]);
            }

            if (!empty(trim($validated['guest_name'] ?? ''))) {
                MeetingParticipant::create([
                    'meeting_id' => $meeting->id,
                    'guest_name' => trim($validated['guest_name']),
                    'participant_role' => 'Guest',
                    'attendance_status' => null,
                ]);
            }

            $report->update(['current_status_id' => $scheduledStatus->id]);

            $report->report_updates()->create([
                'updated_by' => Auth::user()->id,
                'status_id' => $scheduledStatus->id,
                'note' => $validated['note'] ?? null,
            ]);
        });

        return redirect()->route('web.reports.show', $report->id);
    }

    public function destroy(string $id)
    {
        //
    }

    private function staffLevel(?Staff $staff): ?int
    {
        if (!$staff) return null;

        $staff->loadMissing('positions');

        $position = $staff->positions->sortByDesc('pivot.assigned_at')->first();

        return match ($position?->position_name) {
            'Teacher' => 1,
            'Principal' => 2,
            'Ministrong Tagasubaybay' => 3,
            'OSD Officer' => 4,
            default => null,
        };
    }

    private function findStaffForLevel(int $level): ?Staff
    {
        $position = match ($level) {
            1 => 'Teacher',
            2 => 'Principal',
            3 => 'Ministrong Tagasubaybay',
            4 => 'OSD Officer',
            default => null,
        };

        if (!$position) return null;

        return Staff::with('positions')
            ->whereHas('positions', function ($query) use ($position) {
                $query->where('position_name', $position);
            })
            ->orderBy('staff_number')
            ->get()
            ->first(fn ($staff) => $this->staffLevel($staff) === $level);
    }

    private function ensureInitialAssignment(Report $report): void
    {
        $hasAssignment = $report->relationLoaded('latest_assignment')
            ? $report->latest_assignment !== null
            : $report->latest_assignment()->exists();

        if ($hasAssignment || $report->current_status?->status_name !== 'Pending') {
            return;
        }

        if ($report->urgent_safety_flag === null) {
            return;
        }

        if ($report->urgent_safety_flag === true) {
            $ministrong = $this->findStaffForLevel(3);

            if (!$ministrong) return;

            $report->report_assignments()->create([
                'assigned_to' => $ministrong->getKey(),
                'assigned_by' => null,
                'level' => '3',
                'assigned_at' => now(),
                'ended_at' => null,
            ]);

            return;
        }

        $report->loadMissing(['students.grade_sections']);

        $orderedStudents = $report->students->sortByDesc(function ($student) {
            return in_array(
                strtolower((string) $student->pivot->involvement_type),
                ['victim', 'target'],
                true
            ) ? 1 : 0;
        });

        foreach ($orderedStudents as $student) {
            foreach ($student->grade_sections as $gradeSection) {
                if ($gradeSection->pivot?->ended_at !== null || !$gradeSection->adviser) {
                    continue;
                }

                $teacher = Staff::find($gradeSection->adviser);

                if ($teacher && $this->staffLevel($teacher) === 1) {
                    $report->report_assignments()->create([
                        'assigned_to' => $teacher->getKey(),
                        'assigned_by' => null,
                        'level' => '1',
                        'assigned_at' => now(),
                        'ended_at' => null,
                    ]);

                    return;
                }
            }
        }
    }

    private function canViewReport(User $user, Report $report): bool
    {
        if ($user?->parent_guardian && !$user->staff) {
            return (string) $report->reported_by === (string) $user->id;
        }

        $staff = $user?->staff;

        if (!$staff) return false;

        if ($staff->is_admin) return true;

        $staffId = $staff->getKey();

        if ($report->report_assignments()->where('assigned_to', $staffId)->exists()) {
            return true;
        }

        $level = (int) ($report->latest_assignment?->level ?? 0);

        if ($this->staffLevel($staff) === 2 && $level === 1) {
            return true;
        }

        if ($this->staffLevel($staff) === 1 && $level === 0 && $report->current_status?->status_name === 'Pending') {
            $report->loadMissing(['students.grade_sections']);

            return $report->students->contains(function ($student) use ($staffId) {
                $involvement = strtolower((string) $student->pivot->involvement_type);

                if (!in_array($involvement, ['victim', 'target'], true)) {
                    return false;
                }

                return $student->grade_sections->contains(
                    fn ($section) => (string) $section->adviser === (string) $staffId && $section->pivot?->ended_at === null
                );
            });
        }

        return false;
    }

    private function canActOnReport(User $user, Report $report): bool
    {
        $staff = $user?->staff;

        if (!$staff) return false;

        $assignment = $report->latest_assignment;

        if (!$assignment || $assignment->ended_at !== null || (string) $assignment->assigned_to !== (string) $staff->getKey()) {
            return false;
        }

        return $this->staffLevel($staff) === (int) $assignment->level;
    }

    private function authorizeCurrentHandler(Report $report, ?ReportAssignment $assignment): void
    {
        abort_unless(
            $assignment && $assignment->ended_at === null && $this->canActOnReport(Auth::user(), $report),
            403,
            'You are not authorized to perform this action on the current incident handler level.'
        );

        abort_if(
            in_array($report->current_status?->status_name, ['Resolved', 'Dismissed'], true),
            422,
            'This incident is already closed.'
        );
    }
}
