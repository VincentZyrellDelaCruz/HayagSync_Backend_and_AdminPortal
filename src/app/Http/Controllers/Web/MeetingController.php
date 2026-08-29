<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function update(Meeting $meeting, string $action)
    {
        if ($action === 'cancel') {
            $meeting->update(['status' => 'Canceled']);
        } elseif ($action === 'finish') {
            $meeting->update(['status' => 'Finished']);
        }

        return back();
    }

}
