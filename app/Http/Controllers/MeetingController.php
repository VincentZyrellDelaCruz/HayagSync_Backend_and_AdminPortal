<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Report;
use App\Models\TimelineEvent;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function getMeetingsForReport($reportId)
    {
        $meetings = Meeting::where('report_id', $reportId)
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
            'report_id' => 'required|exists:reports,id',
            'meeting_date' => 'required|date',
            'notes' => 'required|string',
            'meeting_type' => 'required|string|in:Virtual,In-Person',
        ]);

        $report = Report::findOrFail($request->report_id);
        $report->update(['status' => 'Scheduled']);

        $roleString = 'Adviser';
        if ($user->role === 'principal') {
            $roleString = 'Principal';
        } elseif ($user->role === 'osd') {
            $roleString = 'OSD Officer';
        }

        $meeting = Meeting::create([
            'report_id' => $report->id,
            'scheduled_by_user_id' => $user->id,
            'scheduled_by_user_name' => $user->name,
            'scheduled_by_user_role' => $roleString,
            'meeting_date' => $request->meeting_date,
            'notes' => $request->notes,
            'meeting_type' => $request->meeting_type,
        ]);

        // Add Timeline Log
        TimelineEvent::create([
            'report_id' => $report->id,
            'title' => 'Meeting Scheduled',
            'description' => "Scheduled {$request->meeting_type} meeting on " . date('Y-m-d H:i', strtotime($request->meeting_date)) . ".",
            'actor_name' => $user->name,
            'actor_role' => $roleString,
        ]);

        return response()->json($meeting, 201);
    }

    public function reschedule(Request $request, $id)
    {
        $user = $request->user();
        $meeting = Meeting::findOrFail($id);

        // Security check: Only the exact user who scheduled it can reschedule
        if ($meeting->scheduled_by_user_id !== $user->id) {
            return response()->json([
                'message' => "Unauthorized. Only the original scheduler ({$meeting->scheduled_by_user_name}) can reschedule."
            ], 403);
        }

        $request->validate([
            'meeting_date' => 'required|date',
            'notes' => 'required|string',
            'meeting_type' => 'required|string|in:Virtual,In-Person',
        ]);

        $meeting->update([
            'meeting_date' => $request->meeting_date,
            'notes' => $request->notes,
            'meeting_type' => $request->meeting_type,
        ]);

        $roleString = 'Adviser';
        if ($user->role === 'principal') {
            $roleString = 'Principal';
        } elseif ($user->role === 'osd') {
            $roleString = 'OSD Officer';
        }

        // Add Timeline Log
        TimelineEvent::create([
            'report_id' => $meeting->report_id,
            'title' => 'Meeting Rescheduled',
            'description' => "Moved meeting to " . date('Y-m-d H:i', strtotime($request->meeting_date)) . ". Notes: {$request->notes}",
            'actor_name' => $user->name,
            'actor_role' => $roleString,
        ]);

        return response()->json($meeting);
    }
}
