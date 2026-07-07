<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'scheduled_by_user_id',
        'scheduled_by_user_name',
        'scheduled_by_user_role',
        'meeting_date',
        'notes',
        'meeting_type',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by_user_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
