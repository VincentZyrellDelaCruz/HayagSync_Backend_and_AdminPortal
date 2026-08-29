<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeSection extends Model
{
    /** @use HasFactory<\Database\Factories\GradeSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'school_year_id',
        'grade_level',
        'section',
        'adviser',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')
                    ->withPivot('school_year_id', 'status', 'enrolled_at', 'ended_at')
                    ->withTimestamps();
    }

    public function school_year(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'adviser');
    }
}
