<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentUpdate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'incident_id',
        'updated_by',
        'status_id',
        'note',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function incident_status(): BelongsTo
    {
        return $this->belongsTo(IncidentStatus::class, 'status_id');
    }
}
