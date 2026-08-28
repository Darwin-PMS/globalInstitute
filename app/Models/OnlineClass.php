<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineClass extends Model
{

    protected $fillable = [
        'course_id', 'batch_id', 'module_id', 'lesson_id', 'teacher_id',
        'title', 'description', 'class_date', 'start_time', 'end_time',
        'duration', 'meeting_provider', 'meeting_url', 'meeting_id',
        'meeting_password', 'recording_url', 'status', 'is_attendance_required',
        'is_recording_available', 'cancelled_reason', 'rescheduled_from', 'created_by',
        'timezone', 'class_type', 'access_policy', 'tenant_id',
        'actual_start_at', 'actual_end_at',
    ];

    protected $casts = [
        'class_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'duration' => 'integer',
        'is_attendance_required' => 'boolean',
        'is_recording_available' => 'boolean',
        'rescheduled_from' => 'datetime',
        'tenant_id' => 'integer',
    ];

    public function course() { return $this->belongsTo(Course::class); }
    public function batch() { return $this->belongsTo(Batch::class); }
    public function module() { return $this->belongsTo(CourseModule::class, 'module_id'); }
    public function lesson() { return $this->belongsTo(Lesson::class); }
    public function teacher() { return $this->belongsTo(Trainer::class, 'teacher_id'); }
    public function attendance() { return $this->hasMany(ClassAttendance::class); }
    public function recordings() { return $this->hasMany(Recording::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    /**
     * The Live Class Room for this class, if one has been provisioned.
     *
     * Polymorphic so the room layer is not coupled to this model — see
     * App\Domains\OnlineLearning\Room.
     */
    public function room()
    {
        return $this->morphOne(\App\Domains\OnlineLearning\Room\Models\ClassRoom::class, 'subject');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'Scheduled',
            'starting_soon' => 'Starting Soon',
            'live' => 'Live',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'postponed' => 'Postponed',
            default => ucfirst($this->status),
        };
    }

    public function isJoinable(): bool
    {
        return in_array($this->status, ['scheduled', 'starting_soon', 'live']) && $this->meeting_url;
    }

    /**
     * `start_time` / `end_time` are cast to Carbon, so interpolating them into a
     * string yields a full datetime ("2026-08-22 10:00:00"). Concatenating that
     * onto the class date produced an unparseable double date. Use these helpers
     * instead of building the timestamp by hand.
     */
    public function timeOnly(?string $attribute): string
    {
        $value = $this->{$attribute};

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('H:i:s');
        }

        return (string) $value;
    }

    public function startsAt(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::parse(
            $this->class_date->toDateString() . ' ' . $this->timeOnly('start_time')
        );
    }

    public function endsAt(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::parse(
            $this->class_date->toDateString() . ' ' . $this->timeOnly('end_time')
        );
    }
}
