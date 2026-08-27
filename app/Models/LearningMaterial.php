<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningMaterial extends Model
{

    protected $fillable = [
        'course_id', 'module_id', 'lesson_id', 'class_id', 'title',
        'description', 'file_path', 'file_type', 'file_size',
        'external_url', 'visibility', 'is_published', 'download_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_published' => 'boolean',
        'download_count' => 'integer',
    ];

    public function course() { return $this->belongsTo(Course::class); }
    public function module() { return $this->belongsTo(CourseModule::class, 'module_id'); }
    public function lesson() { return $this->belongsTo(Lesson::class); }
    public function onlineClass() { return $this->belongsTo(OnlineClass::class, 'class_id'); }
}
