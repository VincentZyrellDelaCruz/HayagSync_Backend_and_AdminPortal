<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    /** @use HasFactory<\Database\Factories\StaffFactory> */
    use HasFactory, SoftDeletes, HasUuids;

    protected $primaryKey = 'user_id';
    public $incrementing = false; 
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'staff_number',
        'department',
        'school_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'staff_position', 'staff_id', 'position_id')->withPivot('assigned_at');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(IncidentCategory::class, 'staff_category', 'staff_id', 'category_id')->withTimestamps();
    }
}
