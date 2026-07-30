<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Meeting;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function getChatMessages(string $meetingId)
    {
        $messages = ChatMessage::with('sender')
            ->where('meeting_id', $meetingId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function markAsRead(Request $request, string $meetingId)
    {
        $user = $request->user();

        ChatMessage::where('meeting_id', $meetingId)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read.']);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'message' => 'required|string',
        ]);

        $message = ChatMessage::create([
            'meeting_id' => $request->meeting_id,
            'sender_id' => $user->id,
            'message'   => trim($request->message),
            'is_read'   => false,
        ]);

        $this->simulateAutoReply($request->meeting_id, $user, trim($request->message));

        return response()->json($message, 201);
    }

    private function simulateAutoReply(string $meetingId, User $currentUser, string $originalMessage)
    {
        $meeting = Meeting::with('scheduler')->findOrFail($meetingId);
        $report  = Report::with('user')->findOrFail($meeting->report_id);

        if ($currentUser->role === 'parent') {
            $staff = $meeting->scheduler;
            if (!$staff) return;

            $reply = "Got your message. Let me review my availability.";
            $msgLower = strtolower($originalMessage);
            if (str_contains($msgLower, 'unavailable')) {
                $reply = "Understood. Please suggest alternative dates.";
            } elseif (str_contains($msgLower, 'thank')) {
                $reply = "You're welcome! See you at the scheduled time.";
            }

            ChatMessage::create([
                'meeting_id' => $meetingId,
                'sender_id'  => $staff->id,
                'message'    => $reply,
                'is_read'    => false,
            ]);
        } else {
            $parent = $report->user;
            if (!$parent) return;

            $reply = "Thank you. I’ll check my schedule.";
            $msgLower = strtolower($originalMessage);
            if (str_contains($msgLower, 'rescheduled')) {
                $reply = "That new schedule works for me.";
            }

            ChatMessage::create([
                'meeting_id' => $meetingId,
                'sender_id'  => $parent->id,
                'message'    => $reply,
                'is_read'    => false,
            ]);
        }
    }
}
