<?php

namespace App\Domains\OnlineLearning\Contracts;

interface MeetingProviderInterface
{
    public function createMeeting(array $data): array;
    public function updateMeeting(string $meetingId, array $data): array;
    public function cancelMeeting(string $meetingId): bool;
    public function getMeeting(string $meetingId): ?array;
    public function getJoinUrl(string $meetingId, bool $isHost = false): ?string;
    public function getHostUrl(string $meetingId): ?string;
}
