<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GradeSection;
use App\Models\IncidentCategory;
use App\Models\MeetingParticipant;
use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\ReportStatus;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $selectedStatus = $req->query('status', 'all');
        $selectedCategory = $req->query('category', 'all');

        $categories = IncidentCategory::where('is_active', true)
            ->orderBy('category_name')
            ->get();

        $reportRelations = [
            'user',
            'current_status',
            'category',
        ];

        if (method_exists(Report::class, 'current_assignee')) {
            $reportRelations[] = 'current_assignee.user';
        }

        $reportsQuery = Report::with($reportRelations);

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

        $allReports = $reportsQuery
            ->get()
            ->sort(function ($a, $b) use ($statusOrder) {
                $statusA = $a->current_status?->status_name ?? '';
                $statusB = $b->current_status?->status_name ?? '';

                $rankA = $statusOrder[$statusA] ?? 99;
                $rankB = $statusOrder[$statusB] ?? 99;

                if ($rankA !== $rankB) {
                    return $rankA <=> $rankB;
                }

                return $b->created_at->timestamp <=> $a->created_at->timestamp;
            })
            ->values();

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $currentItems = $allReports
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $reports = new LengthAwarePaginator(
            $currentItems,
            $allReports->count(),
            $perPage,
            $currentPage,
            [
                'path' => $req->url(),
                'query' => $req->query(),
            ]
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

        return Inertia::render('Reports/Index', compact(
            'reports',
            'categories',
            'statuses',
            'selectedStatus',
            'selectedCategory',
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $report = Report::with('latest_update.report_status')->findOrFail($id);

        if (
            !$report->latest_update ||
            $report->latest_update->report_status?->status_name === 'Pending'
        ) {
            $underInvestigationStatus = ReportStatus::where(
                'status_name',
                'Under Investigation'
            )->first();

            if ($underInvestigationStatus) {
                $report->update([
                    'current_status_id' => $underInvestigationStatus->id,
                ]);

                $report->report_updates()->create([
                    'updated_by' => Auth::user()->id,
                    'status_id' => $underInvestigationStatus->id,
                ]);
            }
        }

        $reportRelations = [
            'user',
            'category',
            'current_status',
            'students',
            'report_evidences',
            'latest_update.report_status',
            'latest_update.user.staff',
            'report_updates' => function ($query) {
                $query->with([
                    'report_status',
                    'user.staff',
                ])->latest();
            },
            'meetings.scheduler',
            'meetings.participants.student',
            'meetings.participants.user',
        ];

        if (method_exists(Report::class, 'current_assignee')) {
            $reportRelations[] = 'current_assignee.user';
        }

        if (method_exists(Report::class, 'assignments')) {
            $reportRelations[] = 'assignments.assignee.user';
            $reportRelations[] = 'assignments.assigner.user';
        }

        $report->load($reportRelations);

        $statuses = ReportStatus::whereNotIn(
            'status_name',
            ['Pending', 'Under Investigation']
        )->get();

        return Inertia::render(
            'Reports/Report',
            compact('report', 'statuses')
        );
    }

    public function streamEvidence(string $id)
    {
        $evidence = ReportEvidence::findOrFail($id);

        abort_unless(
            Auth::check() && Auth::user()->staff,
            403
        );

        $path = Storage::disk('public')->path(
            $evidence->file_path
        );

        abort_unless(
            is_file($path),
            404
        );

        $mimeType = $evidence->mime_type
            ?: File::mimeType(storage_path('app/public/' . $evidence->file_path))
            ?: 'application/octet-stream';

        return response()->file(
            $path,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($evidence->file_name) . '"',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $req, string $id)
    {
        $validated = $req->validate([
            'status_id' => 'nullable|numeric|exists:report_statuses,id',
            'note' => 'nullable|string|max:2000',
            'type' => 'required|string',
            'meeting_datetime' => 'required_if:type,schedule|date|after:now',
            'meeting_type' => 'required_if:type,schedule|in:In-Person,Virtual,Both',
            'purpose' => 'required_if:type,schedule|string|max:2000',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'uuid|exists:students,id',
            'guest_name' => 'nullable|string|max:255',
        ]);

        $report = Report::findOrFail($id);

        if ($validated['type'] === 'schedule') {
            DB::transaction(function () use (
                $report,
                $validated
            ) {
                $scheduledStatus = ReportStatus::where(
                    'status_name',
                    'Scheduled'
                )->firstOrFail();

                $report->update([
                    'current_status_id' => $scheduledStatus->id,
                ]);

                $meeting = $report->meetings()->create([
                    'scheduled_by' => Auth::user()->id,
                    'meeting_date' => $validated['meeting_datetime'],
                    'meeting_type' => $validated['meeting_type'],
                    'purpose' => $validated['purpose'],
                    'notes' => $validated['note'] ?? null,
                    'status' => 'Active',
                ]);

                $participantIds = $validated['participant_ids'] ?? [];

                if (!empty($participantIds)) {
                    $students = $report->students()
                        ->whereIn(
                            'students.id',
                            $participantIds
                        )
                        ->get();

                    foreach ($students as $student) {
                        MeetingParticipant::create([
                            'meeting_id' => $meeting->id,
                            'student_id' => $student->id,
                            'participant_role' => $student->pivot->involvement_type ?? 'Unknown',
                            'attendance_status' => null,
                        ]);
                    }
                }

                if (!empty(trim($validated['guest_name'] ?? ''))) {
                    MeetingParticipant::create([
                        'meeting_id' => $meeting->id,
                        'guest_name' => trim($validated['guest_name']),
                        'participant_role' => 'Guest',
                        'attendance_status' => null,
                    ]);
                }

                $report->report_updates()->create([
                    'updated_by' => Auth::user()->id,
                    'status_id' => $scheduledStatus->id,
                    'note' => $validated['note'] ?? null,
                ]);
            });
        }

        return redirect()->route(
            'web.reports.show',
            $report->id
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /* protected function notifyReport(String $incident_id, String $note)
    {
        $incident = Report::with([
            'students',
            'user.parent_guardian',
            'current_status',
            'latest_update.incident_status',
        ])->findOrFail($incident_id);

        $recipient = $incident->user?->first_name . ' ' . $incident->user->last_name;
        $status = $incident->latest_update?->incident_status?->status_name ?? 'Pending';
        $sender = Auth::user();

        Mail::to($incident->user->email)
            ->send(new IncidentMail($incident, $recipient, $status, $sender, $note));

        Inbox::create([
            'sender_id'   => $sender->id,
            'receiver_id' => $incident->user->id,
            'title'       => "Incident Update: {$status}",
            'message'     => $note,
        ]);

        $deviceTokens = DeviceToken::where('user_id', $incident->user->id)
            ->pluck('token')->toArray();

        if (!empty($deviceTokens)) {
            $fcm = new FcmService();
            $fcm->send(
                $deviceTokens,
                "Incident Update: {$status}",
                $note ?? 'Please check your inbox for details.',
                [
                    'incident_id' => (string) $incident->id,
                    'status'      => $status,
                ]
            );
        }

        return true;

    } */
}
