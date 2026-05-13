<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'email',
        'otp_code',
        'expires_at',
    ];
}
