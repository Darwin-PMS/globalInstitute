<?php

namespace Tests\Feature\OnlineLearning\Room;

use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectResolverInterface;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Services\ClassRoomService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\OnlineLearning\LearningTestFixtures;
use Tests\TestCase;

/**
 * Chat, announcements and the incremental feeds.
 *
 * The delta behaviour matters as much as the permissions: it is what lets the
 * client poll cheaply now and switch to a socket later without changes.
 */
class RoomMessagingTest extends TestCase
{
    use RefreshDatabase;
    use LearningTestFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-25 10:05:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /* ------------------------------------------------------------ posting */

    public function test_a_participant_can_post_a_message(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Could you repeat the last step?']
        )->assertOk()->assertJsonPath('data.message.type', 'message');

        $this->assertDatabaseHas('class_room_messages', [
            'class_room_id' => $room->id,
            'user_id' => $ctx['studentUser']->id,
            'type' => 'message',
        ]);
    }

    public function test_only_the_host_can_post_an_announcement(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Class is cancelled!', 'type' => 'announcement']
        )->assertForbidden();

        $this->actingAs($ctx['teacherUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'We will start in two minutes.', 'type' => 'announcement']
        )->assertOk()->assertJsonPath('data.message.type', 'announcement');

        // Announcements are part of the class record, so they hit the timeline.
        $types = $room->events()->get()->map(fn ($e) => $e->type->value)->all();
        $this->assertContains('announcement_posted', $types);
    }

    public function test_messages_are_rejected_when_the_host_disables_chat(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['teacherUser'])
            ->putJson("/api/online-learning/rooms/{$room->room_key}/settings", ['chat_enabled' => false])
            ->assertOk();

        $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Anyone there?']
        )->assertForbidden();
    }

    public function test_a_message_needs_a_body(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/messages", ['body' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_an_outsider_cannot_post_into_a_room(): void
    {
        [$room] = $this->room();

        $outsider = User::factory()->create();
        $this->makeStudent($outsider);

        $this->actingAs($outsider)->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Let me in']
        )->assertForbidden();

        $this->assertDatabaseCount('class_room_messages', 0);
    }

    /* ------------------------------------------------------------- deltas */

    public function test_the_feed_returns_only_what_followed_the_given_id(): void
    {
        [$room, $ctx] = $this->room();

        $first = $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'First']
        )->json('data.message.id');

        // Initial read: latest page, and a cursor to continue from.
        $initial = $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/messages");

        $initial->assertOk()
            ->assertJsonPath('meta.is_delta', false)
            ->assertJsonPath('meta.last_id', $first);

        $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Second']
        );

        // Delta read: only the new one comes back.
        $delta = $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/messages?after={$first}");

        $delta->assertOk()->assertJsonPath('meta.is_delta', true);

        $bodies = collect($delta->json('data.messages'))->pluck('body');
        $this->assertSame(['Second'], $bodies->all());
    }

    public function test_a_delta_with_nothing_new_returns_an_empty_list(): void
    {
        [$room, $ctx] = $this->room();

        $id = $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Only message']
        )->json('data.message.id');

        $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/messages?after={$id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.messages');
    }

    public function test_the_activity_feed_is_also_incremental(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/enter");

        $initial = $this->actingAs($ctx['teacherUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/activity");

        $initial->assertOk();
        $cursor = $initial->json('meta.last_id');
        $this->assertNotNull($cursor);

        $this->actingAs($ctx['teacherUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/start");

        $delta = $this->actingAs($ctx['teacherUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/activity?after={$cursor}");

        $types = collect($delta->json('data.events'))->pluck('type');
        $this->assertTrue($types->contains('room_started'));
        $this->assertFalse($types->contains('participant_joined'), 'Already-seen events must not repeat.');
    }

    /* --------------------------------------------------------- moderation */

    public function test_a_host_can_remove_a_message_and_it_leaves_the_transcript(): void
    {
        [$room, $ctx] = $this->room();

        $id = $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Something inappropriate']
        )->json('data.message.id');

        $this->actingAs($ctx['teacherUser'])
            ->deleteJson("/api/online-learning/rooms/{$room->room_key}/messages/{$id}")
            ->assertOk();

        $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/messages")
            ->assertOk()
            ->assertJsonCount(0, 'data.messages');

        // Hidden, not destroyed — the record survives for moderation review.
        $this->assertDatabaseHas('class_room_messages', ['id' => $id]);
        $this->assertNotNull(\App\Domains\OnlineLearning\Room\Models\ClassRoomMessage::find($id)->deleted_by_host_at);
    }

    public function test_a_participant_cannot_moderate(): void
    {
        [$room, $ctx] = $this->room();

        $id = $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Mine']
        )->json('data.message.id');

        $this->actingAs($ctx['studentUser'])
            ->deleteJson("/api/online-learning/rooms/{$room->room_key}/messages/{$id}")
            ->assertForbidden();

        $this->actingAs($ctx['studentUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/messages/{$id}/pin", ['pinned' => true])
            ->assertForbidden();
    }

    public function test_pinned_announcements_come_back_on_the_initial_read(): void
    {
        [$room, $ctx] = $this->room();

        $id = $this->actingAs($ctx['teacherUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/messages",
            ['body' => 'Bring your drawings tomorrow.', 'type' => 'announcement']
        )->json('data.message.id');

        $this->actingAs($ctx['teacherUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/messages/{$id}/pin", ['pinned' => true])
            ->assertOk();

        $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data.pinned')
            ->assertJsonPath('data.pinned.0.id', $id);
    }

    public function test_messages_from_one_room_never_appear_in_another(): void
    {
        [$roomA, $ctx] = $this->room();

        $classB = $this->makeClass($ctx['course'], $ctx['batch'], $ctx['trainer'], [
            'title' => 'Second session',
            'class_date' => '2026-08-26',
        ]);
        $roomB = $this->provision($classB);

        $this->actingAs($ctx['studentUser'])->postJson(
            "/api/online-learning/rooms/{$roomA->room_key}/messages",
            ['body' => 'Only for room A']
        )->assertOk();

        $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$roomB->room_key}/messages")
            ->assertOk()
            ->assertJsonCount(0, 'data.messages');
    }

    /* ----------------------------------------------------------- helpers */

    /** @return array{0: ClassRoom, 1: array<string, mixed>} */
    protected function room(): array
    {
        $teacherUser = User::factory()->create(['name' => 'Priya Sharma']);
        $trainer = $this->makeTrainer($teacherUser);

        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        $class = $this->makeClass($course, $batch, $trainer, [
            'class_date' => '2026-08-25',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $studentUser = User::factory()->create(['name' => 'Rahul Mehta']);
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course, $batch);

        return [$this->provision($class), compact(
            'teacherUser', 'trainer', 'course', 'batch', 'class', 'studentUser', 'student'
        )];
    }

    protected function provision($class): ClassRoom
    {
        return app(ClassRoomService::class)
            ->roomFor(app(RoomSubjectResolverInterface::class)->wrap($class));
    }
}
