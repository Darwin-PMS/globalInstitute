<?php

namespace App\Domains\OnlineLearning\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\OnlineLearning\Contracts\StudentProviderInterface;
use App\Domains\OnlineLearning\Contracts\TeacherProviderInterface;
use App\Domains\OnlineLearning\Contracts\CourseProviderInterface;
use App\Domains\OnlineLearning\Contracts\EnrollmentProviderInterface;
use App\Domains\OnlineLearning\Contracts\MeetingProviderInterface;
use App\Domains\OnlineLearning\Contracts\NotificationProviderInterface;
use App\Domains\OnlineLearning\Contracts\AttendanceCalculatorInterface;
use App\Domains\OnlineLearning\Contracts\ConflictDetectionInterface;
use App\Domains\OnlineLearning\Contracts\StorageProviderInterface;
use App\Domains\OnlineLearning\Services\ProgressService;
use App\Domains\OnlineLearning\Services\AttendanceService;
use App\Domains\OnlineLearning\Services\ConflictDetectionService;
use App\Domains\OnlineLearning\Services\RecurrenceService;
use App\Domains\OnlineLearning\Services\NotificationService;
use App\Domains\OnlineLearning\Services\JoinAuthorizationService;
use App\Domains\OnlineLearning\Services\LiveKitService;
use App\Domains\OnlineLearning\Services\LiveClassAttendanceService;
use App\Domains\OnlineLearning\Services\LiveClassParticipantService;
use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectResolverInterface;
use App\Domains\OnlineLearning\Room\Subjects\OnlineClassSubjectResolver;
use App\Domains\OnlineLearning\Room\Services\ClassRoomService;
use App\Domains\OnlineLearning\Room\Services\RoomAccessService;
use App\Domains\OnlineLearning\Room\Services\RoomMessageService;
use App\Domains\OnlineLearning\Room\Services\RoomPresenceService;
use App\Domains\OnlineLearning\Room\Services\RoomTimelineService;

class OnlineLearningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerContracts();
        $this->registerServices();
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../../config/online-learning.php',
            'online-learning'
        );
    }

    protected function registerContracts(): void
    {
        $this->app->bind(StudentProviderInterface::class, InstituteStudentProvider::class);
        $this->app->bind(TeacherProviderInterface::class, InstituteTeacherProvider::class);
        $this->app->bind(CourseProviderInterface::class, InstituteCourseProvider::class);
        $this->app->bind(EnrollmentProviderInterface::class, InstituteEnrollmentProvider::class);
        $this->app->bind(NotificationProviderInterface::class, DatabaseNotificationProvider::class);
        $this->app->bind(AttendanceCalculatorInterface::class, InstituteAttendanceCalculator::class);
        $this->app->bind(ConflictDetectionInterface::class, InstituteConflictDetection::class);

        /*
         * Which scheduled entity the Live Class Room binds to. Swapping this one
         * line repoints the whole room layer at a different backing model.
         */
        $this->app->bind(RoomSubjectResolverInterface::class, OnlineClassSubjectResolver::class);
        $this->app->bind(StorageProviderInterface::class, function ($app) {
            return new InstituteStorageProvider(
                config('online-learning.storage.disk', 'public')
            );
        });

        $this->app->bind(MeetingProviderInterface::class, function ($app) {
            $provider = config('online-learning.meeting_providers.other.driver', ExternalLinkMeetingProvider::class);
            return new $provider();
        });
    }

    protected function registerServices(): void
    {
        $this->app->singleton(ProgressService::class);
        $this->app->singleton(RecurrenceService::class);

        $this->app->singleton(AttendanceService::class, function ($app) {
            return new AttendanceService(
                $app->make(AttendanceCalculatorInterface::class)
            );
        });

        $this->app->singleton(ConflictDetectionService::class, function ($app) {
            return new ConflictDetectionService(
                $app->make(ConflictDetectionInterface::class)
            );
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(NotificationProviderInterface::class)
            );
        });

        $this->app->singleton(JoinAuthorizationService::class, function ($app) {
            return new JoinAuthorizationService(
                $app->make(EnrollmentProviderInterface::class)
            );
        });

        $this->registerRoomServices();
    }

    /**
     * Live Class Room services. Self-contained: they depend only on the room
     * contracts, so this block moves wholesale into a standalone package.
     */
    protected function registerRoomServices(): void
    {
        $this->app->singleton(RoomTimelineService::class);

        $this->app->singleton(ClassRoomService::class, function ($app) {
            return new ClassRoomService(
                $app->make(RoomSubjectResolverInterface::class),
                $app->make(RoomTimelineService::class)
            );
        });

        $this->app->singleton(RoomPresenceService::class, function ($app) {
            return new RoomPresenceService($app->make(RoomTimelineService::class));
        });

        $this->app->singleton(RoomMessageService::class, function ($app) {
            return new RoomMessageService($app->make(RoomTimelineService::class));
        });

        $this->app->singleton(RoomAccessService::class);

        // LiveKit integration services
        $this->app->singleton(LiveKitService::class);

        $this->app->singleton(LiveClassAttendanceService::class, function ($app) {
            return new LiveClassAttendanceService(
                $app->make(LiveKitService::class)
            );
        });

        $this->app->singleton(LiveClassParticipantService::class, function ($app) {
            return new LiveClassParticipantService(
                $app->make(LiveKitService::class)
            );
        });
    }
}
