<?php

namespace App\Http\Controllers\Incidents;

use App\Http\Controllers\Controller;
use App\Mail\IncidentMail;
use App\Models\DeviceToken;
use App\Models\Inbox;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentStatus;
use App\Services\FcmService;
use Google\Client;
use Google\Service\FirebaseCloudMessaging;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class IncidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $selectedStatus = $req->query('status', 'all');
        $selectedCategory = $req->query('category', 'all');

        $categories = IncidentCategory::where('is_active', true)->orderBy('category_name')->get();

        $incidentsQuery = Incident::with(['user', 'current_status', 'category']);

        if ($selectedStatus !== 'all') {
            $incidentsQuery->whereHas('current_status', function ($query) use ($selectedStatus) {
                $query->where('status_name', $selectedStatus);
            });
        }

        if ($selectedCategory !== 'all') {
            $incidentsQuery->where('category_id', $selectedCategory);
        }

        $allIncidents = $incidentsQuery
                ->get()
                ->sortBy([
                    fn ($incident) => match ($incident->current_status?->status_name) {
                        'Pending' => 1,
                        'Under Investigation' => 2,
                        'Scheduled' => 3,
                        'Resolved' => 4,
                        'Unresolved' => 5,
                        'Cancelled' => 6,
                        default => 99,
                    },

                    fn ($incident) => match ($incident->current_status?->status_name) {
                        'Pending' => 1,
                        'Under Investigation' => 2,
                        'Scheduled' => 3,
                        'Resolved' => 4,
                        'Unresolved' => 5,
                        'Cancelled' => 6,
                        default => 99,
                    },

                    fn ($incident) => -$incident->created_at->timestamp,
                ])
                ->values();

            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();

            $currentItems = $allIncidents->slice(($currentPage -1) * $perPage, $perPage)->values();

            $incidents = new LengthAwarePaginator(
                $currentItems,
                $allIncidents->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $req->url(),
                    'query' => $req->query(),
                ]
            );

            $statuses = ['all', 'Pending', 'Under Investigation' , 'Scheduled',
                    'Resolved', 'Unresolved', 'Cancelled'];

        return view('incidents.index', compact(
            'incidents',
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
        $incident = Incident::with('latest_update.incident_status')->findOrFail($id);

        if (!$incident->latest_update ||
            $incident->latest_update->incident_status?->status_name === 'Pending') {

            $incident->update([
                'current_status_id' => 2, // Under Investigation status
            ]);

            $incident->incident_updates()->create([
                'updated_by' => Auth::user()->id,
                'status_id' => 2, // Under Investigation status
            ]);
        }

        $incident->load([
            'user',
            'category',
            'students',
            'incident_evidences',
            'latest_update.incident_status',
            'latest_update.user.staff',
            'incident_updates' => function ($query) {
                $query->with([
                    'incident_status',
                    'user.staff',
                ])->latest();
            },
        ]);

        $statuses = IncidentStatus::whereNotIn('status_name', ['Pending', 'Under Investigation'])->get();

        return view('incidents.incident', compact('incident', 'statuses'));
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
        ]);

        $incident = Incident::findOrFail($id);

        $incident->update([
            'current_status_id' => $validated['status_id'], // Under Investigation status
        ]);

        $incident->incident_updates()->create([
            'updated_by' => Auth::user()->id,
            'status_id' => $validated['status_id'], // Under Investigation status
        ]);

        $this->notifyIncident($incident->id, $validated['note'] ?? '');

        return redirect()->route('web.incidents.show', $incident->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    protected function notifyIncident(String $incident_id, String $note)
    {
        $incident = Incident::with([
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

    }
}
