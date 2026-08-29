<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportAppeal extends Model
{
    use HasUuids, SoftDeletes;

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'report_id',
        'appealed_by',
        'reason',
        'status',
        'reviewed_by'
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function appealed_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appealed_by');
    }

    public function reviewed_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
