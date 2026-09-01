<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'gender',
        'birthdate',
        'email',
        'phone_number',
        'status'
    ];

    public function grade_sections(): BelongsToMany
    {
        return $this->belongsToMany(GradeSection::class, 'enrollments')
                    ->withPivot('school_year_id', 'status', 'enrolled_at', 'ended_at')
                    ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function latestEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class, 'student_id')
            ->ofMany([
                'enrolled_at' => 'max',
                'id' => 'max',
            ]);
    }

    public function latestGradeSection()
    {
        return $this->grade_sections()
            ->orderByDesc('enrollments.enrolled_at')
            ->orderByDesc('enrollments.id')
            ->first();
    }

    public function isAlumni(): bool
    {
        return $this->latestEnrollment?->ended_at !== null;
    }

    public function parent_guardians(): BelongsToMany
    {
        return $this->belongsToMany(ParentGuardian::class, 'student_parent_guardian', 'student_id', 'parent_id')->withPivot('relationship');
    }

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class)->withPivot('involvement_type', 'notes');
    }

    public function disciplinary_actions(): HasMany
    {
        return $this->hasMany(DisciplinaryAction::class, 'student_id');
    }

    public function meeting_participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }
}
