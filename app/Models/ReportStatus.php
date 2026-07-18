<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'status_name',
        'description',
        'sort_order',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'current_status_id');
    }

    public function report_updates(): HasMany
    {
        return $this->hasMany(ReportUpdate::class, 'status_id');
    }
}
