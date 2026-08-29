<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolYear extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolYearFactory> */
    use HasFactory;

    protected $fillable = [
        'school_year',
        'is_active',
    ];

    public function grade_sections(): HasMany
    {
        return $this->hasMany(GradeSection::class, 'school_year_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')
                    ->withPivot('grade_section_id', 'status', 'enrolled_at', 'ended_at')
                    ->withTimestamps();
    }

    public function ai_analysis(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'school_year_id');
    }
}
