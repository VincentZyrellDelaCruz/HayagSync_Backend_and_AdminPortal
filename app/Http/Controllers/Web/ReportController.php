<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\IncidentCategory;
use App\Models\ReportStatus;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $selectedStatus = $req->query('status', 'all');
        $selectedCategory = $req->query('category', 'all');

        $categories = IncidentCategory::where('is_active', true)->orderBy('category_name')->get();

        $reportsQuery = Report::with(['user', 'current_status', 'category']);

        if ($selectedStatus !== 'all') {
            $reportsQuery->whereHas('current_status', function ($query) use ($selectedStatus) {
                $query->where('status_name', $selectedStatus);
            });
        }

        if ($selectedCategory !== 'all') {
            $reportsQuery->where('category_id', $selectedCategory);
        }

        $allReports = $reportsQuery
                ->get()
                ->sortBy([
                    fn ($report) => match ($report->current_status?->status_name) {
                        'Pending' => 1,
                        'Under Investigation' => 2,
                        'Scheduled' => 3,
                        'Resolved' => 4,
                        'Unresolved' => 5,
                        'Cancelled' => 6,
                        default => 99,
                    },

                    fn ($report) => match ($report->current_status?->status_name) {
                        'Pending' => 1,
                        'Under Investigation' => 2,
                        'Scheduled' => 3,
                        'Resolved' => 4,
                        'Unresolved' => 5,
                        'Cancelled' => 6,
                        default => 99,
                    },

                    fn ($report) => -$report->created_at->timestamp,
                ])
                ->values();

            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();

            $currentItems = $allReports->slice(($currentPage -1) * $perPage, $perPage)->values();

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

            $statuses = ['all', 'Pending', 'Under Investigation' , 'Scheduled',
                    'Resolved', 'Dropped'];

        return view('reports.index', compact(
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

        if (!$report->latest_update ||
            $report->latest_update->report_status?->status_name === 'Pending') {

            $report->update([
                'current_status_id' => 2, // Under Investigation status
            ]);

            $report->report_updates()->create([
                'updated_by' => Auth::user()->id,
                'status_id' => 2, // Under Investigation status
            ]);
        }

        $report->load([
            'user',
            'category',
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
        ]);

        $statuses = ReportStatus::whereNotIn('status_name', ['Pending', 'Under Investigation'])->get();

        return view('reports.report', compact('report', 'statuses'));
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
            'status_id' => 'required|numeric',
            'note' => 'nullable|max:200',
            'type' => 'required',
        ]);

        $report = Report::findOrFail($id);

        $report->update([
            'current_status_id' => $validated['status_id'],
        ]);

        if ($validated['type'] === 'schedule') {
            $report->meetings()->create([
                'scheduled_by' => Auth::user()->id,
                'meeting_date' => $req->input('meeting_datetime'),
                'notes' => $validated['note'],
                'status' => 'Active',
            ]);
        }

        $report->report_updates()->create([
            'updated_by' => Auth::user()->id,
            'status_id' => $validated['status_id'], // Under Investigation status
        ]);

        // $this->notifyReport($report->id, $validated['note'] ?? '');

        return redirect()->route('web.reports.show', $report->id);
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
