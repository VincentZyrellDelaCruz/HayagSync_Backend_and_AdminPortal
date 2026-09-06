<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DisciplinaryAction;
use App\Models\IncidentCategory;
use App\Models\MeetingParticipant;
use App\Models\Report;
use App\Models\ReportAssignment;
use App\Models\ReportStatus;
use App\Models\Staff;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $req)
    {
        $selectedStatus = $req->query('status', 'all');
        $selectedCategory = $req->query('category', 'all');

        $categories = Cache::remember(
            'reports:active_categories',
            now()->addMinutes(5),
            fn () => IncidentCategory::where(
                'is_active',
                true
            )
                ->orderBy('category_name')
                ->get()
        );

        $authUser = Auth::user();
        $authStaff = $authUser?->staff;

        abort_unless($authStaff, 403, 'Staff access required.');

        $isAdmin = (bool) $authStaff->is_admin;
        $staffLevel = $this->staffLevel($authStaff);

        $reportsQuery = Report::with(['user', 'current_status', 'category', 'latest_assignment.staff_assigned_to.user']);

        if (!$isAdmin) {
            $reportsQuery->where(function ($query) use ($authStaff, $staffLevel) {
                $query->whereHas('report_assignments', function ($assignmentQuery) use ($authStaff) {
                    $assignmentQuery->where('assigned_to', $authStaff->getKey());
                });

                if ($staffLevel === 2) {
                    $query->orWhere(function ($levelQuery) {
                        $levelQuery
                            ->whereHas('latest_assignment', function ($assignmentQuery) {
                                $assignmentQuery->where('level', '1');
                            })
                            ->orWhere(function ($pendingQuery) {
                                $pendingQuery
                                    ->whereHas('current_status', function ($statusQuery) {
                                        $statusQuery->where('status_name', 'Pending');
                                    })
                                    ->whereDoesntHave('latest_assignment');
                            });
                    });
                }

                if ($staffLevel === 1) {
                    $query->orWhere(function ($teacherQuery) use ($authStaff) {
                        $teacherQuery
                            ->whereHas('current_status', function ($statusQuery) {
                                $statusQuery->where('status_name', 'Pending');
                            })
                            ->whereDoesntHave('latest_assignment', function ($assignmentQuery) {
                                $assignmentQuery->where('level', '1');
                            })
                            ->whereHas('students', function ($studentQuery) use ($authStaff) {
                                $studentQuery
                                    ->whereIn('report_student.involvement_type', ['victim', 'Victim', 'target', 'Target'])
                                    ->whereHas('grade_sections', function ($sectionQuery) use ($authStaff) {
                                        $sectionQuery->where('adviser', $authStaff->getKey());
                                    });
                            });
                    });
                }
            });
        }

        if ($selectedStatus !== 'all') {
            $reportsQuery->whereHas('current_status', function ($query) use ($selectedStatus) {
                $query->where('status_name', $selectedStatus);
            });
        }

        if ($selectedCategory !== 'all') {
            $reportsQuery->where('category_id', $selectedCategory);
        }

        $statusOrder = [
            'Pending' => 1,
            'Under Investigation' => 2,
            'Scheduled' => 3,
            'Escalated' => 4,
            'Resolved' => 5,
            'Dismissed' => 6,
        ];

        $reports = $reportsQuery->leftJoin(
                'report_statuses as sorting_status',
                'reports.current_status_id',
                '=',
                'sorting_status.id'
            )->select('reports.*')->orderByRaw(
                "CASE sorting_status.status_name
                    WHEN 'Pending' THEN {$statusOrder['Pending']}
                    WHEN 'Under Investigation' THEN {$statusOrder['Under Investigation']}
                    WHEN 'Scheduled' THEN {$statusOrder['Scheduled']}
                    WHEN 'Escalated' THEN {$statusOrder['Escalated']}
                    WHEN 'Resolved' THEN {$statusOrder['Resolved']}
                    WHEN 'Dismissed' THEN {$statusOrder['Dismissed']}
                    ELSE 99
                END ASC"
            )->orderByDesc('reports.created_at')->paginate(10)->withQueryString();

        $reports->getCollection()->transform(
            function ($report) use ($authUser) {
                $assignment = $report->latest_assignment;

                $report->current_level = $assignment ? (int) $assignment->level : null;
                $report->current_assignee = $assignment?->staff_assigned_to;
                $report->can_act = $this->canActOnReport($authUser, $report);
                $report->read_only = !$report->can_act;

                return $report;
            }
        );

        $statuses = [
            'all',
            'Pending',
            'Under Investigation',
            'Scheduled',
            'Escalated',
            'Resolved',
            'Dismissed',
        ];

        return Inertia::render(
            'Reports/Index',
            compact('reports', 'categories', 'statuses', 'selectedStatus', 'selectedCategory')
        );
    }

    public function create()
    {
        /* NotificationService::send(
            receiver: $teacher->user,
            type: 'report_submitted',
            title: 'New Incident Report',
            message: 'A new incident report has been submitted and assigned to you for initial review.',
            actionUrl: route(
                'web.reports.show',
                $report->id
            ),
            priority: 'high',
            data: [
                'report_id' => (string) $report->id,
                'report_code' => $report->report_code ?? null,
            ]
        ); */
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        $report = Report::with(['current_status', 'latest_assignment'])->findOrFail($id);

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
            'report_updates' => function ($query) {
                $query->with(['report_status', 'user.staff'])->latest();
            },
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
        $evidence = \App\Models\ReportEvidence::findOrFail($id);

        abort_unless(Auth::check() && Auth::user()->staff, 403);

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

    public function forward(Request $request, string $id)
    {
        $validated = $request->validate([
            'note' => 'required|string|min:5|max:2000',
        ]);

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

    public function resolve(Request $request, string $id)
    {
        $report = Report::with(['current_status', 'latest_assignment', 'students'])->findOrFail($id);

        $this->authorizeCurrentHandler($report, $report->latest_assignment);

        $currentLevel = (int) ($report->latest_assignment?->level ?? 0);

        $validated = $request->validate([
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

    public function dismiss(Request $request, string $id)
    {
        $validated = $request->validate([
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

        if (!$position) {
            return null;
        }

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

        $report->loadMissing(['students.grade_sections']);

        $students = $report->students;

        $orderedStudents = $students->sortByDesc(function ($student) {
            return in_array(
                strtolower((string) $student->pivot->involvement_type),
                ['victim', 'target'],
                true
            ) ? 1 : 0;
        });

        foreach ($orderedStudents as $student) {
            foreach ($student->grade_sections as $gradeSection) {
                if ($gradeSection->pivot?->ended_at !== null ||!$gradeSection->adviser) {
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

    private function canViewReport($user, Report $report): bool
    {
        $staff = $user?->staff;

        if (!$staff) {
            return false;
        }

        if ($staff->is_admin) {
            return true;
        }

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

                return $student->grade_sections->contains(fn ($section) => (string) $section->adviser === (string) $staffId && $section->pivot?->ended_at === null);
            });
        }

        return false;
    }

    private function canActOnReport($user, Report $report): bool
    {
        $staff = $user?->staff;

        if (!$staff) {
            return false;
        }

        $assignment = $report->latest_assignment;

        if (!$assignment || $assignment->ended_at !== null || (string) $assignment->assigned_to !== (string) $staff->getKey()) {
            return false;
        }

        return $this->staffLevel($staff) === (int) $assignment->level;
    }

    private function authorizeCurrentHandler(Report $report, ?ReportAssignment $assignment): void
    {
        abort_unless($assignment && $assignment->ended_at === null && $this->canActOnReport(Auth::user(), $report), 403, 'You are not authorized to perform this action on the current incident handler level.');

        abort_if(in_array($report->current_status?->status_name, ['Resolved', 'Dismissed'], true), 422, 'This incident is already closed.');
    }
}
