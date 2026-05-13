<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'gender',
        'birthdate',
        'email',
        'phone_number',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function parent_guardians(): BelongsToMany
    {
        return $this->belongsToMany(ParentGuardian::class, 'student_parent_guardian', 'student_id', 'parent_id')->withPivot('relationship');
    }

    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class)->withPivot('involvement_type', 'notes');
    }

    public function pending_users(): HasMany
    {
        return $this->hasMany(PendingRegistration::class);
    }
}
