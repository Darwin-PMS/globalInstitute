<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'description',
        'duration',
        'sequence',
        'status',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    public function lessons(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lesson::class, 'module_id');
    }
}
