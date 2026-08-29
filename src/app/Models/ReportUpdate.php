<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportUpdate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'report_id',
        'updated_by',
        'status_id',
        'note',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function report_status(): BelongsTo
    {
        return $this->belongsTo(ReportStatus::class, 'status_id');
    }
}
