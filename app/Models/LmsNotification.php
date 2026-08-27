<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsNotification extends Model
{
    protected $table = 'lms_notifications';

    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'data', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
