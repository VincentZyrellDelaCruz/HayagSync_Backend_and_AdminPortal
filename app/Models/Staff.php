<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'staff_number',
        'is_admin'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'staff_position', 'staff_id', 'position_id')->withPivot('assigned_at');
    }

    public function section_advisers(): HasMany
    {
        return $this->hasMany(GradeSection::class, 'adviser');
    }

    public function disciplinary_actions(): HasMany
    {
        return $this->hasMany(DisciplinaryAction::class, 'staff_id');
    }

    public function latestPosition()
    {
        return $this->positions()->orderByDesc('staff_position.assigned_at')->first();
    }

    /*
    public function reviewed_appeal(): BelongsTo
    {
        return $this->belongsTo(ReportAppeal::class, 'reviewed_by');
    } */
}
