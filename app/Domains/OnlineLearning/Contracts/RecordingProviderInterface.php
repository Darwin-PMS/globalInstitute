<?php

namespace App\Domains\OnlineLearning\Contracts;

interface RecordingProviderInterface
{
    public function getRecordingUrl(string $recordingId): ?string;
    public function getRecordingDuration(string $recordingId): ?int;
    public function getRecordingStatus(string $recordingId): string;
    public function deleteRecording(string $recordingId): bool;
}
