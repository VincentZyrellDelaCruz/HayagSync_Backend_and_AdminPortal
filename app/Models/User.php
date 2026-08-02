<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'first_name',
    'last_name',
    'middle_name',
    'suffix',
    'gender',
    'birthdate',
    'email',
    'password',
    'phone_number',
    'profile_image_url',
    'status'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function activity_logs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function parent_guardian(): HasOne
    {
        return $this->hasOne(ParentGuardian::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_by');
    }

    public function report_evidences(): HasMany
    {
        return $this->hasMany(ReportEvidence::class, 'uploaded_by');
    }

    public function report_updates(): HasMany
    {
        return $this->hasMany(ReportUpdate::class, 'updated_by');
    }

    public function inbox_sender(): HasMany
    {
        return $this->hasMany(Inbox::class, 'sender_id');
    }

    public function inbox_receiver(): HasMany
    {
        return $this->hasMany(Inbox::class, 'receiver_id');
    }

    public function report_appeal(): HasMany
    {
        return $this->HasMany(ReportAppeal::class, 'appealed_by');
    }

    public function reviewed_appeal(): HasMany
    {
        return $this->HasMany(ReportAppeal::class, 'reviewed_by');
    }

    public function login_history(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class);
    }

    public function security_events(): HasMany
    {
        return $this->hasMany(SecurityEvent::class);
    }

    /**
     * Get meetings scheduled by this staff.
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'scheduled_by_user_id');
    }
}
