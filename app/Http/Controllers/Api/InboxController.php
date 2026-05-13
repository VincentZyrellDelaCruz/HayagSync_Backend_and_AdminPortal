<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller
{
    public function index(): JsonResponse
    {
        $user_id = Auth::user()->id;

        $inboxes = Inbox::with(['sender', 'receiver'])
            ->where(function ($query) use ($user_id) {
                $query->where('sender_id', $user_id)
                    ->orWhere('receiver_id', $user_id);
            })
            ->get();

        return response()->json($inboxes);
    }


    public function show(String $id): JsonResponse
    {
        $inbox = Inbox::with([
            'sender',
            'receiver',
        ])->findOrFail($id);

        return response()->json($inbox);
    }
}
