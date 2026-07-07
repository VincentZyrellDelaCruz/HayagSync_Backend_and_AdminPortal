<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Meeting;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function getChatMessages($meetingId)
    {
        $messages = ChatMessage::where('meeting_id', $meetingId)
            ->orderBy('created_at', 'asc')
            ->get();
        return response()->json($messages);
    }

    public function markAsRead(Request $request, $meetingId)
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

        $roleString = 'Parent';
        if ($user->role === 'adviser') {
            $roleString = 'Adviser';
        } elseif ($user->role === 'principal') {
            $roleString = 'Principal';
        } elseif ($user->role === 'osd') {
            $roleString = 'OSD Officer';
        }

        $message = ChatMessage::create([
            'meeting_id' => $request->meeting_id,
            'sender_id' => $user->id,
            'sender_name' => $user->name,
            'sender_role' => $roleString,
            'message' => trim($request->message),
            'is_read' => false,
        ]);

        // --- SIMULATED CHAT AUTO-REPLY ---
        $this->simulateAutoReply($request->meeting_id, $user, trim($request->message));

        return response()->json($message, 201);
    }

    private function simulateAutoReply($meetingId, $currentUser, $originalMessage)
    {
        $meeting = Meeting::findOrFail($meetingId);
        $report = Report::findOrFail($meeting->report_id);

        if ($currentUser->role === 'parent') {
            // Reply from scheduling staff
            $staff = User::find($meeting->scheduled_by_user_id);
            if (! $staff) return;

            $reply = "Got your message. Let me review my availability and get back to you soon.";
            $msgLower = strtolower($originalMessage);
            if (str_contains($msgLower, 'unavailable') || str_contains($msgLower, 'cannot')) {
                $reply = "Understood. Please let me know what alternative dates or times work best for you.";
            } elseif (str_contains($msgLower, 'thank') || str_contains($msgLower, 'ok')) {
                $reply = "You're welcome! See you at the scheduled time.";
            }

            ChatMessage::create([
                'meeting_id' => $meetingId,
                'sender_id' => $staff->id,
                'sender_name' => $staff->name,
                'sender_role' => $staff->role === 'principal' ? 'Principal' : ($staff->role === 'osd' ? 'OSD Officer' : 'Adviser'),
                'message' => $reply,
                'is_read' => false,
            ]);
        } else {
            // Reply from Parent
            $parent = User::find($report->parent_id);
            if (! $parent) return;

            $reply = "Thank you. I have noted this and will check my schedule.";
            $msgLower = strtolower($originalMessage);
            if (str_contains($msgLower, 'rescheduled') || str_contains($msgLower, 'move')) {
                $reply = "That new schedule works for me. Thank you for accommodating!";
            }

            ChatMessage::create([
                'meeting_id' => $meetingId,
                'sender_id' => $parent->id,
                'sender_name' => $parent->name,
                'sender_role' => 'Parent',
                'message' => $reply,
                'is_read' => false,
            ]);
        }
    }
}
