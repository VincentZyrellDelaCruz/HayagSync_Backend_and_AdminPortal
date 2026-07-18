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
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'gender',
        'birthdate',
        'email',
        'phone_number',
        'grade_section_id',
        'status'
    ];

    public function parent_guardians(): BelongsToMany
    {
        return $this->belongsToMany(ParentGuardian::class, 'student_parent_guardian', 'student_id', 'parent_id')->withPivot('relationship');
    }

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class)->withPivot('involvement_type', 'notes');
    }

    public function grade_sections(): BelongsTo
    {
        return $this->belongsTo(GradeSection::class, 'grade_section_id');
    }

    public function disciplinary_actions(): HasMany
    {
        return $this->hasMany(DisciplinaryAction::class, 'student_id');
    }
}
