<?php

namespace App\Http\Controllers;

use App\Models\TimelineEvent;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function getTimelineEvents($reportId)
    {
        $events = TimelineEvent::where('report_id', $reportId)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($events);
    }

    public function store(Request $request, $reportId)
    {
        $user = $request->user();
        $request->validate([
            'description' => 'required|string',
        ]);

        $event = TimelineEvent::create([
            'report_id' => $reportId,
            'title' => 'Staff Note Added',
            'description' => $request->description,
            'actor_name' => $user->name,
            'actor_role' => $user->role === 'principal' ? 'Principal' : ($user->role === 'osd' ? 'OSD Officer' : 'Adviser'),
        ]);

        return response()->json($event, 201);
    }
}
