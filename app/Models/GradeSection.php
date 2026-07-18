<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'grade_section_id');
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
