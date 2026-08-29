<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Report;
use App\Models\TimelineEvent;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function getMeetingsForReport($reportId)
    {
        $meetings = Meeting::with('scheduler')
            ->where('report_id', $reportId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($meetings);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'parent') {
            return response()->json(['message' => 'Parents cannot schedule meetings.'], 403);
        }

        $request->validate([
            'report_id'    => 'required|exists:reports,id',
            'meeting_date' => 'required|date',
            'notes'        => 'required|string',
            'meeting_type' => 'required|string|in:Virtual,In-Person',
        ]);

        $report = Report::findOrFail($request->report_id);
        $report->update(['status' => 'Scheduled']);

        $meeting = Meeting::create([
            'report_id'    => $report->id,
            'scheduled_by' => $user->id,
            'meeting_date' => $request->meeting_date,
            'notes'        => $request->notes,
            'status'       => 'Active',
            'meeting_type' => $request->meeting_type,
        ]);

        TimelineEvent::create([
            'report_id'   => $report->id,
            'title'       => 'Meeting Scheduled',
            'description' => "Scheduled {$request->meeting_type} meeting on " . date('M d, Y h:i A', strtotime($request->meeting_date)),
            'actor_name'  => $user->name,
            'actor_role'  => ucfirst($user->role),
        ]);

        return response()->json($meeting, 201);
    }

    public function reschedule(Request $request, $id)
    {
        $user = $request->user();
        $meeting = Meeting::findOrFail($id);

        if ($meeting->scheduled_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized. Only the original scheduler can reschedule.'], 403);
        }

        $request->validate([
            'meeting_date' => 'required|date',
            'notes'        => 'required|string',
            'meeting_type' => 'required|string|in:Virtual,In-Person',
        ]);

        $meeting->update([
            'meeting_date' => $request->meeting_date,
            'notes'        => $request->notes,
            'meeting_type' => $request->meeting_type,
        ]);

        TimelineEvent::create([
            'report_id'   => $meeting->report_id,
            'title'       => 'Meeting Rescheduled',
            'description' => "Rescheduled to " . date('M d, Y h:i A', strtotime($request->meeting_date)) . ". Notes: {$request->notes}",
            'actor_name'  => $user->name,
            'actor_role'  => ucfirst($user->role),
        ]);

        return response()->json($meeting);
    }
}
