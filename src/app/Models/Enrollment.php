<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'grade_section_id',
        'school_year_id',
        'status',
        'enrolled_at',
        'ended_at',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
        'ended_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function grade_section(): BelongsTo
    {
        return $this->belongsTo(GradeSection::class);
    }

    public function school_year(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
