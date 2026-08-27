<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{

    protected $fillable = [
        'online_class_id', 'title', 'description', 'recording_url',
        'duration', 'visibility', 'is_published', 'view_count',
    ];

    protected $casts = [
        'duration' => 'integer',
        'is_published' => 'boolean',
        'view_count' => 'integer',
    ];

    public function onlineClass() { return $this->belongsTo(OnlineClass::class); }

    public function course()
    {
        return $this->hasOneThrough(
            Course::class,
            OnlineClass::class,
            'id',
            'id',
            'online_class_id',
            'course_id'
        );
    }
}
