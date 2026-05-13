<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingRegistration extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'gender',
        'birthdate',
        'occupation',
        'phone_number',
        'student_id',
        'relationship',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(student::class);
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(RegisterProof::class, 'pending_id');
    }
}
