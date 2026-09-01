<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAssignment extends Model
{
    use HasUuids;

    protected $casts = [
        'assigned_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $fillable = [
        'report_id',
        'assigned_to',
        'assigned_by',
        'level',
        'assigned_at',
        'ended_at',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function staff_assigned_to(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to', 'user_id');
    }

    public function staff_assigned_by(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_by', 'user_id');
    }


}
