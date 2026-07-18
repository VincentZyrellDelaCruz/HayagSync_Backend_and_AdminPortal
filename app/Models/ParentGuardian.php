<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentGuardian extends Model
{
    /** @use HasFactory<\Database\Factories\ParentGuardianFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'parent_code',
        'occupation',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_parent_guardian', 'parent_id', 'student_id')->withPivot('relationship');
    }

    /* public function report_appeal(): BelongsTo
    {
        return $this->belongsTo(ReportAppeal::class, 'appealed_by');
    } */
}
