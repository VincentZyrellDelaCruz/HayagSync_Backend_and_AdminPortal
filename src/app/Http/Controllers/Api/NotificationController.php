<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $notifications = Inbox::query()->with('sender:id,first_name,last_name')
            ->where('receiver_id', $userId)
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->limit(20)
            ->get();

        $unreadCount = Inbox::query()->where('receiver_id', $userId)
            ->where('is_read', false)
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Inbox $inbox)
    {
        abort_unless((string) $inbox->receiver_id === (string) Auth::id(), 403);

        if (!$inbox->is_read) {
            $inbox->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function markAllRead()
    {
        Inbox::query()->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
