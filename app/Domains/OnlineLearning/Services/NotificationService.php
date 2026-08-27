<?php

namespace App\Domains\OnlineLearning\Services;

use App\Models\LmsNotification;
use App\Domains\OnlineLearning\Contracts\NotificationProviderInterface;

class NotificationService
{
    public function __construct(
        protected NotificationProviderInterface $provider
    ) {}

    public function send(int $userId, string $title, string $message, string $type, array $data = []): bool
    {
        LmsNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
        ]);

        return $this->provider->send($userId, $title, $message, $type, $data);
    }

    public function sendBulk(array $userIds, string $title, string $message, string $type, array $data = []): bool
    {
        foreach ($userIds as $userId) {
            LmsNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data,
            ]);
        }

        return $this->provider->sendBulk($userIds, $title, $message, $type, $data);
    }

    public function classScheduled(array $studentIds, string $className, string $date, string $time): bool
    {
        return $this->sendBulk(
            $studentIds,
            'Class Scheduled',
            "A new class '{$className}' has been scheduled for {$date} at {$time}.",
            'class_scheduled'
        );
    }

    public function classCancelled(array $studentIds, string $className, string $reason = ''): bool
    {
        return $this->sendBulk(
            $studentIds,
            'Class Cancelled',
            "The class '{$className}' has been cancelled." . ($reason ? " Reason: {$reason}" : ''),
            'class_cancelled'
        );
    }

    public function classRescheduled(array $studentIds, string $className, string $newDate, string $newTime): bool
    {
        return $this->sendBulk(
            $studentIds,
            'Class Rescheduled',
            "The class '{$className}' has been rescheduled to {$newDate} at {$newTime}.",
            'class_rescheduled'
        );
    }

    public function recordingAvailable(array $studentIds, string $className): bool
    {
        return $this->sendBulk(
            $studentIds,
            'Recording Available',
            "The recording for '{$className}' is now available.",
            'recording_available'
        );
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->provider->getUnreadCount($userId);
    }
}
