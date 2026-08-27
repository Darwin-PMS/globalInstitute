<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Domains\OnlineLearning\Contracts\MeetingProviderInterface;

class ExternalLinkMeetingProvider implements MeetingProviderInterface
{
    public function createMeeting(array $data): array
    {
        return [
            'meeting_id' => $data['meeting_id'] ?? null,
            'join_url' => $data['join_url'] ?? null,
            'host_url' => $data['host_url'] ?? null,
            'password' => $data['password'] ?? null,
        ];
    }

    public function updateMeeting(string $meetingId, array $data): array
    {
        return $data;
    }

    public function cancelMeeting(string $meetingId): bool
    {
        return true;
    }

    public function getMeeting(string $meetingId): ?array
    {
        return null;
    }

    public function getJoinUrl(string $meetingId, bool $isHost = false): ?string
    {
        return null;
    }

    public function getHostUrl(string $meetingId): ?string
    {
        return null;
    }
}
