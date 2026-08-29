<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginHistory extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'device_name',
        'browser',
        'ip_address',
        'location',
        'login_time',
    ];

    protected $casts = [
        'login_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
