<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $appends = ['latest_position'];

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

    protected function latestPosition(): Attribute
    {
        return Attribute::get(fn () => $this->relationLoaded('positions')
            ? $this->positions->sortByDesc('pivot.assigned_at')->first()
            : $this->positions()->orderByDesc('staff_position.assigned_at')->first()
        );
    }

    public function resolved_security(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'resolved_by');
    }

    public function report_assigned_to(): HasMany
    {
        return $this->hasMany(ReportAssignment::class, 'assigned_to', 'user_id');
    }

    public function report_assigned_by(): HasMany
    {
        return $this->hasMany(ReportAssignment::class, 'assigned_by', 'user_id');
    }

    /*
    public function reviewed_appeal(): BelongsTo
    {
        return $this->belongsTo(ReportAppeal::class, 'reviewed_by');
    } */
}
