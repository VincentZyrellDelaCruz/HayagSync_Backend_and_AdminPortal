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

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'reported_by',
        'category_id',
        'current_status_id',
        'incident_title',
        'description',
        'location',
        'incident_date',
        'incident_time',
        'ai_summary',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'category_id');
    }

    // This was used for Report cases that recently reported, no staff intervention yet
    public function current_status(): BelongsTo
    {
        return $this->belongsTo(ReportStatus::class, 'current_status_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withPivot('involvement_type', 'notes');
    }

    public function report_evidences(): HasMany
    {
        return $this->hasMany(ReportEvidence::class);
    }

    public function report_updates(): HasMany
    {
        return $this->hasMany(ReportUpdate::class, 'report_id');
    }

    public function latest_update(): HasOne
    {
        return $this->hasOne(ReportUpdate::class, 'report_id')->latestOfMany();
    }

    public function disciplinary_actions(): HasMany
    {
        return $this->hasMany(DisciplinaryAction::class, 'report_id');
    }

    public function appeals(): HasMany
    {
        return $this->masMany(ReportAppeal::class, 'report_id');
    }

    public function meetings(): HasMany
    {
        return $this->masMany(Meeting::class, 'report_id');
    }

}
