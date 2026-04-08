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
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUuids, SoftDeletes;

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

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by');
    }

    public function incident_evidences(): HasMany
    {
        return $this->hasMany(IncidentEvidence::class, 'uploaded_by');
    }

    public function incident_updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class, 'updated_by');
    }

    public function disciplinary_posts(): HasMany
    {
        return $this->hasMany(DisciplinaryPost::class, 'created_by');
    }
}
