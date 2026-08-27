<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Lesson extends Model
{

    protected $fillable = [
        'course_id', 'module_id', 'title', 'slug', 'description', 'content',
        'video_url', 'duration', 'sequence', 'is_free', 'is_published', 'status',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'duration' => 'integer',
        'sequence' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lesson $lesson) {
            if (!$lesson->slug) {
                $lesson->slug = Str::slug($lesson->title);
            }
        });
    }

    public function course() { return $this->belongsTo(Course::class); }
    public function module() { return $this->belongsTo(CourseModule::class, 'module_id'); }
    public function classes() { return $this->hasMany(OnlineClass::class); }
    public function materials() { return $this->hasMany(LearningMaterial::class); }
    public function assignments() { return $this->hasMany(Assignment::class); }
    public function progress() { return $this->hasMany(StudentLessonProgress::class); }
}
