<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function grade_section(): HasMany
    {
        return $this->hasMany(GradeSection::class, 'school_year_id');
    }

    public function ai_analysis(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'school_year_id');
    }
}
