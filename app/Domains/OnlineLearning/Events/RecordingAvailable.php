<?php

namespace App\Domains\OnlineLearning\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecordingAvailable
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $recordingId,
        public int $classId,
        public int $courseId,
        public array $studentIds,
        public string $className,
    ) {}
}
