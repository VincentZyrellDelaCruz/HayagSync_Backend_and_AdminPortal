<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'severity',
        'event_type',
        'description',
        'ip_address',
        'location',
        'status',
        'resolved_at',
        'resolved_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'resolved_by');
    }
}
