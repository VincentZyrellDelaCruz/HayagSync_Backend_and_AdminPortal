<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    /** @use HasFactory<\Database\Factories\IncidentFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'reported_by',
        'category_id',
        'current_status_id',
        'incident_title',
        'description',
        'incident_datetime',
        'location',
        'latitude',
        'longitude',
        'urgency_level',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'category_id');
    }

    // This was used for incident cases that recently reported, no staff intervention yet
    public function current_status(): BelongsTo
    {
        return $this->belongsTo(IncidentStatus::class, 'current_status_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withPivot('involvement_type', 'notes');
    }

    public function incident_evidences(): HasMany
    {
        return $this->hasMany(IncidentEvidence::class);
    }

    public function incident_updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class, 'incident_id');
    }

    public function latest_update(): HasOne
    {
        return $this->hasOne(IncidentUpdate::class, 'incident_id')->latestOfMany();
    }

    public function ai_guidance(): HasOne
    {
        return $this->hasOne(AiGuidance::class);
    }

}
