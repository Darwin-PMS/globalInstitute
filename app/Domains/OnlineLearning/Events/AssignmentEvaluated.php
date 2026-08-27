<?php

namespace App\Domains\OnlineLearning\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentEvaluated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $submissionId,
        public int $studentId,
        public int $assignmentId,
        public int $marks,
        public int $maxMarks,
    ) {}
}
