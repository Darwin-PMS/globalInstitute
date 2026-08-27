<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Models\LmsNotification;
use App\Domains\OnlineLearning\Contracts\NotificationProviderInterface;

class DatabaseNotificationProvider implements NotificationProviderInterface
{
    public function send(int $userId, string $title, string $message, string $type, array $data = []): bool
    {
        LmsNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
        ]);

        return true;
    }

    public function sendBulk(array $userIds, string $title, string $message, string $type, array $data = []): bool
    {
        if ($userIds === []) {
            return true;
        }

        // insert() bypasses model casts, so the json column has to be encoded
        // here - passing the raw array through would fail on conversion.
        $encoded = json_encode($data);

        $records = array_map(fn ($userId) => [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $encoded,
            'created_at' => now(),
            'updated_at' => now(),
        ], array_values(array_unique($userIds)));

        LmsNotification::insert($records);

        return true;
    }

    public function markAsRead(int $notificationId): bool
    {
        $notification = LmsNotification::find($notificationId);
        if ($notification) {
            $notification->update(['read_at' => now()]);
            return true;
        }
        return false;
    }

    public function getUnreadCount(int $userId): int
    {
        return LmsNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
