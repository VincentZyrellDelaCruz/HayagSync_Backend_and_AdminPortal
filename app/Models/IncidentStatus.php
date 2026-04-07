<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'status_name',
        'description',
        'sort_order',
    ];

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'current_status_id');
    }

    public function incident_updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class, 'status_id');
    }
}
