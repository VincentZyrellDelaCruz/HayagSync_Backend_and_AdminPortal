<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImportRow extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'identifier',
        'validation_status',
        'processing_status',
        'action',
        'raw_data',
        'errors',
        'before_data',
        'after_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'errors' => 'array',
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DataImportBatch::class, 'batch_id');
    }
}
