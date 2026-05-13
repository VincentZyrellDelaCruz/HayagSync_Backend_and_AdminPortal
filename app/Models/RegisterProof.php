<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegisterProof extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'pending_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'proof_type',
        'hash_signature',
    ];

    public function pendings(): BelongsTo
    {
        return $this->belongsTo(PendingRegistration::class, 'pending_id');
    }
}
