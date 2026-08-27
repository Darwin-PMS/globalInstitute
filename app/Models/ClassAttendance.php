<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassAttendance extends Model
{
    protected $table = 'class_attendance';

    protected $fillable = [
        'online_class_id', 'student_id', 'enrollment_id', 'status',
        'joined_at', 'left_at', 'notes', 'marked_by',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function onlineClass() { return $this->belongsTo(OnlineClass::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function enrollment() { return $this->belongsTo(Enrollment::class); }
    public function marker() { return $this->belongsTo(User::class, 'marked_by'); }
}
