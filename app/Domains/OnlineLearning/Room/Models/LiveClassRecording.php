<?php

namespace App\Domains\OnlineLearning\Room\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveClassRecording extends Model
{
    use SoftDeletes;

    protected $table = 'live_class_recordings';

    protected $fillable = [
        'class_room_id',
        'live_class_id',
        'egress_id',
        'room_name',
        'status',
        'title',
        'description',
        'started_at',
        'ended_at',
        'duration_seconds',
        'file_url',
        'thumbnail_url',
        'storage_provider',
        'file_size',
        'visibility',
        'is_published',
        'view_count',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'file_size' => 'integer',
        'is_published' => 'boolean',
        'view_count' => 'integer',
        'metadata' => 'array',
    ];

    // ─── Relationships ─────────────────────────────────────────

    public function room(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function liveClass(): BelongsTo
    {
        return $this->belongsTo(\App\Models\OnlineClass::class, 'live_class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeReady($query)
    {
        return $query->where('status', 'ready')->where('is_published', true);
    }

    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('class_room_id', $roomId);
    }

    public function scopeVisible($query, string $visibility = 'students')
    {
        return $query->where('visibility', $visibility);
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function getFormattedDurationAttribute(): string
    {
        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getFileSizeFormattedAttribute(): ?string
    {
        if (!$this->file_size) return null;

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function markReady(string $fileUrl, ?int $durationSeconds = null, ?int $fileSize = null): void
    {
        $this->update([
            'status' => 'ready',
            'file_url' => $fileUrl,
            'duration_seconds' => $durationSeconds ?? $this->duration_seconds,
            'file_size' => $fileSize ?? $this->file_size,
        ]);
    }

    public function markFailed(string $reason = ''): void
    {
        $this->update([
            'status' => 'failed',
            'metadata' => array_merge($this->metadata ?? [], ['failure_reason' => $reason]),
        ]);
    }

    public function incrementView(): void
    {
        $this->increment('view_count');
    }
}
