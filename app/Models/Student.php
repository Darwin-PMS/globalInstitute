<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id',
        'father_name',
        'mother_name',
        'date_of_birth',
        'gender',
        'whatsapp',
        'address',
        'city',
        'state',
        'pin_code',
        'qualification',
        'institution',
        'passing_year',
        'experience',
        'emergency_contact_name',
        'emergency_relationship',
        'emergency_contact_number',
        'photo',
        'id_proof',
        'qualification_proof',
        'other_documents',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passing_year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(\App\Models\Enrollment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(\App\Models\StudentDocument::class);
    }
}
