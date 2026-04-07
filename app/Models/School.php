<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_code',
        'school_name',
        'campus_name',
        'street_address',
        'barangay',
        'city',
        'contact_number',
        'email_address',
        'website_url',
        'permit_number',
        'recognition_number',
        'principal',
        'date_established',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function school_years(): HasMany
    {
        return $this->hasMany(SchoolYear::class);
    }

    public function staffs(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
