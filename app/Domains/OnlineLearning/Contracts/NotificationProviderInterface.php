<?php

namespace App\Domains\OnlineLearning\Contracts;

interface NotificationProviderInterface
{
    public function send(int $userId, string $title, string $message, string $type, array $data = []): bool;
    public function sendBulk(array $userIds, string $title, string $message, string $type, array $data = []): bool;
    public function markAsRead(int $notificationId): bool;
    public function getUnreadCount(int $userId): int;
}
