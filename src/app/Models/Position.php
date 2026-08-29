<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'position_name',
        'department'
    ];

    public function staffs(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_position', 'position_id', 'staff_id')->withPivot('assigned_at');
    }

    public function ai_analysis(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'position_id');
    }
}
