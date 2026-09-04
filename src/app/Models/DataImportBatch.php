<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataImportBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'initiated_by',
        'import_type',
        'file_name',
        'file_path',
        'file_hash',
        'mode',
        'status',
        'stage',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'processed_rows',
        'created_count',
        'updated_count',
        'deactivated_count',
        'progress',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(DataImportRow::class, 'batch_id');
    }
}
