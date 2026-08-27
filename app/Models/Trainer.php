<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'designation',
        'qualification',
        'experience',
        'specialization',
        'bio',
        'photo',
        'status',
        'sort_order',
        'phone',
        'email',
        'employment_type',
        'joining_date',
        'notes',
    ];

    protected $casts = [
        'joining_date' => 'date',
    ];

    /**
     * The login this trainer teaches under. Nullable: a trainer can be listed
     * on the public site without holding a portal account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(\App\Models\TeacherAvailability::class, 'teacher_id');
    }

    public function batchAssignments(): HasMany
    {
        return $this->hasMany(\App\Models\TeacherBatchAssignment::class, 'teacher_id');
    }

    public function batchSchedules(): HasMany
    {
        return $this->hasMany(\App\Models\BatchSchedule::class, 'teacher_id');
    }
}
