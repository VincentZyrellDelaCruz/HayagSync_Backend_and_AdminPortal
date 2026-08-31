<?php

namespace App\Observers;

use App\Models\Meeting;
use Illuminate\Support\Carbon;

class MeetingObserver
{
    public function creating(Meeting $meeting): void
    {
        $year = Carbon::now()->year;

        // Get the last meeting_code for this year
        $lastCode = Meeting::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->value('meeting_code');

        if ($lastCode) {
            // Format: MTG-2026-00001
            $number = (int) substr($lastCode, 9); // strip "MTG-YYYY-"
            $newNumber = str_pad($number + 1, 5, '0', STR_PAD_LEFT);
            $meeting->meeting_code = "MTG-{$year}-{$newNumber}";
        } else {
            // First record for this year
            $meeting->meeting_code = "MTG-{$year}-00001";
        }
    }
}
