<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'document_type',
        'document_name',
        'file_path',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }
}
