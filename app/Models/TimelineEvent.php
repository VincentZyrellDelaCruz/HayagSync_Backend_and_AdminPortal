<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimelineEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'title',
        'description',
        'actor_name',
        'actor_role',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
