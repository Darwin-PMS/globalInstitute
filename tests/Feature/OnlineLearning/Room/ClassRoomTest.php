<?php

namespace Tests\Feature\OnlineLearning\Room;

use App\Domains\OnlineLearning\Room\Enums\RoomState;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Services\ClassRoomService;
use App\Models\OnlineClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\OnlineLearning\LearningTestFixtures;
use Tests\TestCase;

/**
 * Room provisioning, identity and the state machine.
 */
class ClassRoomTest extends TestCase
{
    use RefreshDatabase;
    use LearningTestFixtures;

    /* ------------------------------------------------------- provisioning */

    public function test_each_class_gets_exactly_one_room_provisioned_on_first_use(): void
    {
        $class = $this->scheduledClass();

        $admin = $this->makeAdmin();

        $first = $this->actingAs($admin)->getJson("/api/online-learning/classes/{$class->id}/room");
        $first->assertOk()->assertJsonPath('success', true);

        $key = $first->json('data.room_key');
        $this->assertNotEmpty($key);

        // Asking again must return the same room, not create a second one.
        $second = $this->actingAs($admin)->getJson("/api/online-learning/classes/{$class->id}/room");
        $second->assertOk()->assertJsonPath('data.room_key', $key);

        $this->assertDatabaseCount('class_rooms', 1);
        $this->assertDatabaseHas('class_rooms', [
            'subject_type' => OnlineClass::class,
            'subject_id' => $class->id,
        ]);
    }

    public function test_two_classes_get_independent_rooms(): void
    {
        $trainer = $this->makeTrainer();
        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        $a = $this->makeClass($course, $batch, $trainer, ['title' => 'Session A']);
        $b = $this->makeClass($course, $batch, $trainer, [
            'title' => 'Session B',
            'class_date' => now()->addDay()->toDateString(),
        ]);

        $admin = $this->makeAdmin();

        $keyA = $this->actingAs($admin)->getJson("/api/online-learning/classes/{$a->id}/room")->json('data.room_key');
        $keyB = $this->actingAs($admin)->getJson("/api/online-learning/classes/{$b->id}/room")->json('data.room_key');

        $this->assertNotSame($keyA, $keyB, 'Each class must have its own room.');
        $this->assertDatabaseCount('class_rooms', 2);
    }

    public function test_rooms_are_addressed_by_an_opaque_key_not_a_sequential_id(): void
    {
        $room = $this->roomFor($this->scheduledClass());

        // A uuid, so a room URL cannot be walked to reach another class.
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $room->room_key
        );
        $this->assertSame('room_key', $room->getRouteKeyName());
    }

    public function test_a_room_carries_its_own_settings_copy(): void
    {
        $class = $this->scheduledClass(['is_attendance_required' => false]);
        $room = $this->roomFor($class);

        // Settings are materialised per room, seeded from the subject's rules.
        $this->assertIsArray($room->settings);
        $this->assertFalse($room->setting('attendance_tracking'));
        $this->assertTrue($room->setting('chat_enabled'));
    }

    /* -------------------------------------------------------- state machine */

    public function test_the_lobby_opens_by_itself_once_the_window_is_reached(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 09:00:00'));

        $class = $this->scheduledClass([
            'class_date' => '2026-08-25',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $room = $this->roomFor($class);
        $this->assertSame(RoomState::Scheduled, $room->state);

        // Default lobby window is 15 minutes.
        Carbon::setTestNow(Carbon::parse('2026-08-25 09:50:00'));

        $service = app(ClassRoomService::class);
        $room = $service->syncState($room->refresh(), $service->resolveSubject($room));

        $this->assertSame(RoomState::Open, $room->state);
        $this->assertNotNull($room->opened_at);

        Carbon::setTestNow();
    }

    public function test_time_alone_never_makes_a_room_live(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 10:30:00'));

        $class = $this->scheduledClass([
            'class_date' => '2026-08-25',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $room = $this->roomFor($class);
        $service = app(ClassRoomService::class);
        $room = $service->syncState($room, $service->resolveSubject($room));

        // Past the start time, but nobody has hosted it — an unattended class
        // is not a live class.
        $this->assertSame(RoomState::Open, $room->state);
        $this->assertFalse($room->isLive());

        Carbon::setTestNow();
    }

    public function test_a_room_closes_itself_after_the_grace_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 13:00:00'));

        $class = $this->scheduledClass([
            'class_date' => '2026-08-25',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $room = $this->roomFor($class);
        $service = app(ClassRoomService::class);
        $room = $service->syncState($room, $service->resolveSubject($room));

        // 30-minute default grace after the scheduled end has elapsed.
        $this->assertSame(RoomState::Ended, $room->state);
        $this->assertNotNull($room->ended_at);

        Carbon::setTestNow();
    }

    public function test_a_cancelled_class_produces_a_cancelled_room(): void
    {
        $class = $this->scheduledClass(['status' => 'cancelled', 'cancelled_reason' => 'Instructor unwell.']);
        $room = $this->roomFor($class);

        $this->assertSame(RoomState::Cancelled, $room->state);
        $this->assertSame('Instructor unwell.', $room->cancellation_reason);
    }

    public function test_illegal_transitions_are_ignored_rather_than_throwing(): void
    {
        $room = $this->roomFor($this->scheduledClass());
        $service = app(ClassRoomService::class);

        $room = $service->end($room);
        $this->assertSame(RoomState::Ended, $room->state);

        // A double-clicked Start after the class ended must be harmless.
        $room = $service->start($room);
        $this->assertSame(RoomState::Ended, $room->state, 'Ended rooms must not go back to live.');
    }

    public function test_lifecycle_changes_are_written_to_the_timeline(): void
    {
        $room = $this->roomFor($this->scheduledClass());
        $service = app(ClassRoomService::class);

        $service->open($room);
        $service->start($room->refresh());
        $service->end($room->refresh());

        // `type` is an enum cast, so read the backing values.
        $types = $room->events()->get()->map(fn ($e) => $e->type->value)->all();

        $this->assertContains('room_opened', $types);
        $this->assertContains('room_started', $types);
        $this->assertContains('room_ended', $types);
    }

    /* ----------------------------------------------------------- helpers */

    protected function scheduledClass(array $overrides = []): OnlineClass
    {
        $trainer = $this->makeTrainer($overrides['host'] ?? null);
        unset($overrides['host']);

        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        return $this->makeClass($course, $batch, $trainer, $overrides);
    }

    protected function roomFor(OnlineClass $class): ClassRoom
    {
        $service = app(ClassRoomService::class);
        $resolver = app(\App\Domains\OnlineLearning\Room\Contracts\RoomSubjectResolverInterface::class);

        return $service->roomFor($resolver->wrap($class));
    }
}
