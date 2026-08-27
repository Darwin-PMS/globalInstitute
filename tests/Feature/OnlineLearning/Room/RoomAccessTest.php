<?php

namespace Tests\Feature\OnlineLearning\Room;

use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectResolverInterface;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Services\ClassRoomService;
use App\Models\OnlineClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\OnlineLearning\LearningTestFixtures;
use Tests\TestCase;

/**
 * Who may enter a room, in what role, and what each role is shown.
 *
 * These are the rules that must hold regardless of what the client sends.
 */
class RoomAccessTest extends TestCase
{
    use RefreshDatabase;
    use LearningTestFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        // Inside the lobby window for a 10:00 class.
        Carbon::setTestNow(Carbon::parse('2026-08-25 09:55:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /* ------------------------------------------------------------- roles */

    public function test_the_assigned_teacher_is_the_host(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['teacherUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}")
            ->assertOk()
            ->assertJsonPath('data.me.role', 'host')
            ->assertJsonPath('data.me.can_control_room', true)
            ->assertJsonPath('data.me.can_mark_attendance', true);
    }

    public function test_an_enrolled_student_is_a_participant_without_controls(): void
    {
        [$room, $ctx] = $this->room();

        $response = $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}");

        $response->assertOk()
            ->assertJsonPath('data.me.role', 'participant')
            ->assertJsonPath('data.me.can_control_room', false)
            ->assertJsonPath('data.me.can_mark_attendance', false)
            ->assertJsonPath('data.me.can_post_announcement', false)
            ->assertJsonPath('data.me.can_view_roster', false);
    }

    public function test_an_admin_moderates_any_room(): void
    {
        [$room] = $this->room();

        $this->actingAs($this->makeAdmin())
            ->getJson("/api/online-learning/rooms/{$room->room_key}")
            ->assertOk()
            ->assertJsonPath('data.me.role', 'moderator')
            ->assertJsonPath('data.me.can_control_room', true);
    }

    public function test_someone_outside_the_class_cannot_open_the_room(): void
    {
        [$room] = $this->room();

        $outsider = User::factory()->create();
        $this->makeStudent($outsider); // a student, but not of this course

        $this->actingAs($outsider)
            ->getJson("/api/online-learning/rooms/{$room->room_key}")
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_a_guest_is_rejected(): void
    {
        [$room] = $this->room();

        $this->getJson("/api/online-learning/rooms/{$room->room_key}")->assertUnauthorized();
    }

    /* -------------------------------------------------- entering the room */

    public function test_entering_records_presence_and_only_then_returns_the_meeting_link(): void
    {
        [$room, $ctx] = $this->room();

        // The bootstrap payload must never carry the link.
        $bootstrap = $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}");
        $bootstrap->assertOk()->assertJsonMissingPath('data.meeting_url');

        $enter = $this->actingAs($ctx['studentUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/enter");

        $enter->assertOk()->assertJsonPath('data.meeting_url', 'https://meet.google.com/abc-defg-hij');

        $this->assertDatabaseHas('class_room_participants', [
            'class_room_id' => $room->id,
            'user_id' => $ctx['studentUser']->id,
            'role' => 'participant',
        ]);
    }

    public function test_a_participant_is_not_given_the_host_credentials(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/enter")
            ->assertOk()
            ->assertJsonPath('data.meeting_id', null)
            ->assertJsonPath('data.meeting_password', null);
    }

    public function test_a_student_cannot_enter_before_the_lobby_opens(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 08:00:00'));

        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/enter")
            ->assertForbidden()
            ->assertJsonMissingPath('data.meeting_url');

        $this->assertDatabaseCount('class_room_participants', 0);
    }

    public function test_the_host_may_enter_early_to_set_up(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 08:00:00'));

        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['teacherUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/enter")
            ->assertOk()
            ->assertJsonPath('data.meeting_url', 'https://meet.google.com/abc-defg-hij');
    }

    public function test_re_entering_accumulates_time_instead_of_starting_over(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/enter")->assertOk();

        // Ten minutes of presence, then a disconnect and a rejoin.
        Carbon::setTestNow(Carbon::parse('2026-08-25 10:05:00'));
        $this->actingAs($ctx['studentUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/heartbeat")->assertOk();
        $this->actingAs($ctx['studentUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/leave")->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-08-25 10:20:00'));
        $this->actingAs($ctx['studentUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/enter")->assertOk();

        $participant = $room->participants()->where('user_id', $ctx['studentUser']->id)->first();

        $this->assertSame(2, $participant->join_count);

        // 09:55 -> 10:05 is ten minutes. Carbon 3 returns a SIGNED difference, so
        // the original argument order silently recorded zero for everyone.
        $this->assertSame(600, $participant->total_seconds, 'The first stretch must be credited in full.');
    }

    public function test_measured_presence_drives_the_attendance_proposal(): void
    {
        [$room, $ctx] = $this->room();

        // Present from 09:55 to 11:50 of a 10:00-12:00 class.
        $this->actingAs($ctx['studentUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/enter")->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-08-25 11:50:00'));
        $this->actingAs($ctx['studentUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/heartbeat")->assertOk();

        $response = $this->actingAs($ctx['teacherUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/attendance");

        $response->assertOk();

        $proposal = collect($response->json('data.proposal'))
            ->firstWhere('user_id', $ctx['studentUser']->id);

        $this->assertNotNull($proposal, 'The student should appear in the proposal.');
        $this->assertGreaterThan(0, $proposal['attended_seconds']);
        $this->assertSame('present', $proposal['suggested_status']);
    }

    public function test_a_host_saving_attendance_writes_it_against_the_class(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['teacherUser'])->postJson(
            "/api/online-learning/rooms/{$room->room_key}/attendance",
            ['records' => [['user_id' => $ctx['studentUser']->id, 'status' => 'present', 'notes' => 'On time']]]
        )->assertOk();

        // The room translates room users into the student ids attendance is keyed by.
        $this->assertDatabaseHas('class_attendance', [
            'online_class_id' => $ctx['class']->id,
            'student_id' => $ctx['student']->id,
            'status' => 'present',
        ]);

        $types = $room->events()->get()->map(fn ($e) => $e->type->value)->all();
        $this->assertContains('attendance_marked', $types);
    }

    /* ---------------------------------------------------- host lifecycle */

    public function test_only_the_host_can_start_or_end_the_class(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/start")
            ->assertForbidden();

        $this->assertNotSame('live', $room->fresh()->state->value, 'A participant must not be able to start the class.');

        $this->actingAs($ctx['teacherUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/start")
            ->assertOk()
            ->assertJsonPath('data.room.state', 'live');

        $this->actingAs($ctx['studentUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/end")
            ->assertForbidden();

        $this->actingAs($ctx['teacherUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/end")
            ->assertOk()
            ->assertJsonPath('data.room.state', 'ended');
    }

    public function test_only_the_host_can_change_room_settings(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])
            ->putJson("/api/online-learning/rooms/{$room->room_key}/settings", ['chat_enabled' => false])
            ->assertForbidden();

        $this->actingAs($ctx['teacherUser'])
            ->putJson("/api/online-learning/rooms/{$room->room_key}/settings", ['chat_enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.room.settings.chat_enabled', false);
    }

    public function test_settings_are_scoped_to_one_room(): void
    {
        [$roomA, $ctx] = $this->room();

        // A second class hosted by the same teacher.
        $classB = $this->makeClass($ctx['course'], $ctx['batch'], $ctx['trainer'], [
            'title' => 'Second session',
            'class_date' => '2026-08-26',
        ]);
        $roomB = $this->provision($classB);

        $this->actingAs($ctx['teacherUser'])
            ->putJson("/api/online-learning/rooms/{$roomA->room_key}/settings", ['chat_enabled' => false])
            ->assertOk();

        // Turning chat off in one classroom must not touch the other.
        $this->assertFalse($roomA->fresh()->setting('chat_enabled'));
        $this->assertTrue($roomB->fresh()->setting('chat_enabled'));
    }

    /* ------------------------------------------------------------ panels */

    public function test_a_participant_cannot_read_the_roster_or_attendance(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/roster")
            ->assertForbidden();

        $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/attendance")
            ->assertForbidden();

        $this->actingAs($ctx['studentUser'])
            ->postJson("/api/online-learning/rooms/{$room->room_key}/attendance", [
                'records' => [['user_id' => $ctx['studentUser']->id, 'status' => 'present']],
            ])
            ->assertForbidden();
    }

    public function test_presence_does_not_leak_participant_names_to_participants(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['studentUser'])->postJson("/api/online-learning/rooms/{$room->room_key}/enter");

        // A participant sees a count, not a list of who else is in the class.
        $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/presence")
            ->assertOk()
            ->assertJsonMissingPath('data.online');

        $this->actingAs($ctx['teacherUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/presence")
            ->assertOk()
            ->assertJsonStructure(['data' => ['online', 'online_count', 'host_present']]);
    }

    public function test_hiding_materials_removes_them_for_participants_but_not_the_host(): void
    {
        [$room, $ctx] = $this->room();

        $this->actingAs($ctx['teacherUser'])
            ->putJson("/api/online-learning/rooms/{$room->room_key}/settings", ['materials_visible' => false])
            ->assertOk();

        $this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/materials")
            ->assertForbidden();

        $this->actingAs($ctx['teacherUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}/materials")
            ->assertOk();
    }

    public function test_the_panel_list_reflects_the_role(): void
    {
        [$room, $ctx] = $this->room();

        $hostPanels = collect($this->actingAs($ctx['teacherUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}")
            ->json('data.panels'))->pluck('key');

        $studentPanels = collect($this->actingAs($ctx['studentUser'])
            ->getJson("/api/online-learning/rooms/{$room->room_key}")
            ->json('data.panels'))->pluck('key');

        $this->assertTrue($hostPanels->contains('attendance'));
        $this->assertTrue($hostPanels->contains('roster'));

        $this->assertFalse($studentPanels->contains('attendance'));
        $this->assertFalse($studentPanels->contains('roster'));
        $this->assertTrue($studentPanels->contains('stage'));
        $this->assertTrue($studentPanels->contains('chat'));
    }

    /* ----------------------------------------------------------- helpers */

    /**
     * A class with a host teacher and one enrolled student, plus its room.
     *
     * @return array{0: ClassRoom, 1: array<string, mixed>}
     */
    protected function room(array $overrides = []): array
    {
        $teacherUser = User::factory()->create(['name' => 'Priya Sharma']);
        $trainer = $this->makeTrainer($teacherUser);

        $course = $this->makeCourse();
        $batch = $this->makeBatch($course, $trainer);

        $class = $this->makeClass($course, $batch, $trainer, array_merge([
            'class_date' => '2026-08-25',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ], $overrides));

        $studentUser = User::factory()->create(['name' => 'Rahul Mehta']);
        $student = $this->makeStudent($studentUser);
        $this->enroll($student, $course, $batch);

        return [$this->provision($class), compact(
            'teacherUser', 'trainer', 'course', 'batch', 'class', 'studentUser', 'student'
        )];
    }

    protected function provision(OnlineClass $class): ClassRoom
    {
        $service = app(ClassRoomService::class);

        return $service->roomFor(app(RoomSubjectResolverInterface::class)->wrap($class));
    }
}
