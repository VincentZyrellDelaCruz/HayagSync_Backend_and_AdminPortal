<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingParticipant extends Model
{
    use HasUuids;

    protected $fillable = [
        'meeting_id',
        'student_id',
        'user_id',
        'guest_name',
        'participant_role',
        'attendance_status',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
