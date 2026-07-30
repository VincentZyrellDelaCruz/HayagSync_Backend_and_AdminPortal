<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'report_id'   => $reportId,
            'title'       => 'Note Added',
            'description' => $request->description,
            'actor_name'  => $user->name,
            'actor_role'  => ucfirst($user->role),
        ]);

        return response()->json($event, 201);
    }
}
