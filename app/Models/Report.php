<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'parent_name',
        'student_name',
        'student_section',
        'student_grade',
        'title',
        'description',
        'category',
        'incident_date',
        'incident_location',
        'evidence_url',
        'evidence_verification_state',
        'evidence_verification_details',
        'ai_summary',
        'status',
        'discipline_action',
        'discipline_notes',
        'appealed_reason',
        'appeal_count',
        'bully_name',
        'bully_grade_section',
        'witnesses',
    ];

    protected $casts = [
        'incident_date' => 'datetime',
        'appeal_count' => 'integer',
        'witnesses' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }
}
