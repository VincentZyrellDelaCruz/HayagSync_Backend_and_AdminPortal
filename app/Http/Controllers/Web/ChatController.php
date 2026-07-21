<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function store(Request $request, Meeting $meeting)
    {
        $request->validate(['message' => 'required|string|max:500']);

        if ($meeting->status !== 'Active') {
            return back()->with('error', 'Cannot send messages to inactive meetings.');
        }

        $meeting->chatMessages()->create([
            'sender_id' => Auth::id(),
            'message'   => $request->message,
            'is_read'   => false,
        ]);

        return back();
    }

}
