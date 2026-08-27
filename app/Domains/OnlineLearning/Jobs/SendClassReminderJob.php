<?php

namespace App\Domains\OnlineLearning\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domains\OnlineLearning\Services\NotificationService;
use App\Models\OnlineClass;

class SendClassReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public int $classId,
        public int $minutesBefore,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $class = OnlineClass::with(['course', 'batch'])->find($this->classId);

        if (!$class || $class->status === 'cancelled') {
            return;
        }

        $studentIds = \App\Models\Enrollment::where('course_id', $class->course_id)
            ->where('batch_id', $class->batch_id)
            ->where('status', 'active')
            ->pluck('student_id')
            ->toArray();

        if (empty($studentIds)) {
            return;
        }

        $notificationService->sendBulk(
            $studentIds,
            'Class Reminder',
            "Reminder: '{$class->title}' starts in {$this->minutesBefore} minutes.",
            'class_reminder',
            ['class_id' => $this->classId]
        );
    }
}
