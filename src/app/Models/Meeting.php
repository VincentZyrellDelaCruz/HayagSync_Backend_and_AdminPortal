<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'report_id',
        'scheduled_by',
        'meeting_date',
        'meeting_type',
        'purpose',
        'notes',
        'status',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }
}
