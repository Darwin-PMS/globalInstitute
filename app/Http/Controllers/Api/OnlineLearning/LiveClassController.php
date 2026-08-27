<?php

namespace App\Http\Controllers\Api\OnlineLearning;

use App\Domains\OnlineLearning\Services\LiveKitService;
use App\Domains\OnlineLearning\Services\LiveClassAttendanceService;
use App\Domains\OnlineLearning\Services\LiveClassParticipantService;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Models\ClassRoomParticipant;
use App\Domains\OnlineLearning\Room\Models\ClassRoomMessage;
use App\Domains\OnlineLearning\Room\Enums\RoomState;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OnlineLearning\JoinLiveClassRequest;
use App\Http\Requests\Api\OnlineLearning\SendLiveClassMessageRequest;
use App\Http\Requests\Api\OnlineLearning\UpdateParticipantPermissionRequest;
use App\Http\Requests\Api\OnlineLearning\StartRecordingRequest;
use App\Models\OnlineClass;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveClassController extends Controller
{
    public function __construct(
        protected LiveKitService $liveKit,
        protected LiveClassAttendanceService $attendanceService,
        protected LiveClassParticipantService $participantService,
    ) {}

    // ─── Join & Connect ────────────────────────────────────────────

    /**
     * POST /api/live-classes/{id}/join
     * Generate LiveKit token and join info.
     */
    public function join(OnlineClass $onlineClass, JoinLiveClassRequest $request): JsonResponse
    {
        $user = $request->user();

        // Resolve or create room
        $room = ClassRoom::where('subject_type', OnlineClass::class)
            ->where('subject_id', $onlineClass->id)
            ->first();

        if (!$room) {
            return $this->fail('Class room not found. Please contact support.', 404);
        }

        // Check join authorization — admins and hosts may enter from Scheduled state
        if (!$room->state->isEnterable()) {
            $isAdmin = $user->isAdministrator() || $user->is_admin;
            $isHost = $room->host_user_id === $user->id;
            if (!($isAdmin || $isHost) || !$room->state->canTransitionTo(\App\Domains\OnlineLearning\Room\Enums\RoomState::Open)) {
                return $this->fail('This class is not currently available for joining.', 422);
            }
            // Auto-open room for admin/host entry
            $room->update(['state' => \App\Domains\OnlineLearning\Room\Enums\RoomState::Open]);
            $room->refresh();
        }

        // Determine role
        $role = $this->resolveRole($user, $onlineClass, $room);

        // Check if locked
        if ($room->setting('is_locked', false) && $role === RoomRole::Participant) {
            $isAlreadyIn = $room->participants()->where('user_id', $user->id)->exists();
            if (!$isAlreadyIn) {
                return $this->fail('This classroom is locked. Please wait for the instructor to unlock it.', 423);
            }
        }

        // Ensure LiveKit room exists
        try {
            $room = $this->liveKit->ensureRoom($room);
        } catch (\Exception $e) {
            Log::error('Failed to ensure LiveKit room', ['room_id' => $room->id, 'error' => $e->getMessage()]);
            return $this->fail('Unable to connect to the live classroom. Please try again.', 503);
        }

        // Register/update participant in database
        $participant = $this->registerParticipant($room, $user, $role);

        // Get or create permissions
        $permissions = $this->resolvePermissions($room, $user, $role);

        // Generate LiveKit token
        try {
            $token = $this->liveKit->generateToken(
                $room,
                $user,
                $role->value,
                $permissions,
            );
        } catch (\Exception $e) {
            Log::error('Failed to generate LiveKit token', ['room_id' => $room->id, 'error' => $e->getMessage()]);
            return $this->fail('Failed to generate access token. Please try again.', 500);
        }

        // Sync participant count
        $this->liveKit->syncParticipantCount($room);

        return $this->ok([
            'live_class' => [
                'id' => $onlineClass->id,
                'title' => $onlineClass->title,
                'status' => $onlineClass->status,
                'course' => $onlineClass->course?->only('id', 'title', 'slug'),
                'teacher' => $onlineClass->teacher?->user?->only('id', 'name', 'avatar'),
            ],
            'room' => [
                'id' => $room->id,
                'room_key' => $room->room_key,
                'state' => $room->state->value,
                'settings' => $room->settings,
            ],
            'livekit_url' => config('livekit.url'),
            'token' => $token,
            'participant' => [
                'id' => $participant->id,
                'role' => $role->value,
                'display_name' => $user->name,
                'is_hand_raised' => $participant->is_hand_raised,
                'is_camera_enabled' => $participant->is_camera_enabled,
                'is_microphone_enabled' => $participant->is_microphone_enabled,
            ],
            'permissions' => $permissions,
        ]);
    }

    // ─── Classroom Lifecycle ───────────────────────────────────────

    /**
     * POST /api/live-classes/{id}/start
     * Instructor starts the class.
     */
    public function start(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        if (!$user->isAdministrator() && $room->host_user_id !== $user->id) {
            return $this->fail('Only the instructor can start this class.', 403);
        }

        if ($room->state !== RoomState::Open && $room->state !== RoomState::Scheduled) {
            return $this->fail('Cannot start class in current state: ' . $room->state->value, 422);
        }

        // Ensure LiveKit room
        $room = $this->liveKit->ensureRoom($room);

        // Transition state
        $room->update([
            'state' => RoomState::Live,
            'started_at' => now(),
        ]);

        // Record event
        $room->events()->create([
            'type' => 'room_started',
            'user_id' => $user->id,
            'description' => "Class started by {$user->name}",
        ]);

        // Update LiveKit room metadata
        $this->liveKit->updateRoomMetadata($room->livekit_room_name, [
            'state' => 'live',
            'started_at' => now()->toISOString(),
            'title' => $onlineClass->title,
        ]);

        // Update online_class status
        $onlineClass->update([
            'status' => 'live',
            'actual_start_at' => now(),
        ]);

        Log::info('Live class started', [
            'room_id' => $room->id,
            'online_class_id' => $onlineClass->id,
            'started_by' => $user->id,
        ]);

        return $this->ok([
            'room' => [
                'id' => $room->id,
                'state' => $room->state->value,
                'started_at' => $room->started_at?->toISOString(),
            ],
        ], 'Class started successfully.');
    }

    /**
     * POST /api/live-classes/{id}/end
     * Instructor ends the class.
     */
    public function end(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        if (!$user->isAdministrator() && $room->host_user_id !== $user->id) {
            return $this->fail('Only the instructor can end this class.', 403);
        }

        if ($room->state->isFinished()) {
            return $this->fail('Class has already ended.', 422);
        }

        // Stop any active recording
        if ($room->active_egress_id) {
            $this->liveKit->stopRecording($room->active_egress_id);
        }

        // Calculate attendance
        $attendance = $this->attendanceService->calculateAttendance($room);

        // Transition state
        $room->update([
            'state' => RoomState::Ended,
            'ended_at' => now(),
            'active_egress_id' => null,
        ]);

        // Record event
        $room->events()->create([
            'type' => 'room_ended',
            'user_id' => $user->id,
            'description' => "Class ended by {$user->name}",
        ]);

        // Update online_class status
        $onlineClass->update([
            'status' => 'completed',
            'actual_end_at' => now(),
        ]);

        // Delete LiveKit room
        if ($room->livekit_room_name) {
            $this->liveKit->deleteRoom($room->livekit_room_name);
            $room->update(['livekit_room_active' => false]);
        }

        Log::info('Live class ended', [
            'room_id' => $room->id,
            'online_class_id' => $onlineClass->id,
            'attendance_count' => count($attendance),
        ]);

        return $this->ok([
            'room' => [
                'id' => $room->id,
                'state' => $room->state->value,
                'ended_at' => $room->ended_at?->toISOString(),
            ],
            'attendance_summary' => [
                'total' => count($attendance),
                'present' => count(array_filter($attendance, fn($a) => $a['attendance_status'] === 'present')),
                'absent' => count(array_filter($attendance, fn($a) => $a['attendance_status'] === 'absent')),
            ],
        ], 'Class ended successfully.');
    }

    // ─── Participant Management ────────────────────────────────────

    /**
     * GET /api/live-classes/{id}/participants
     */
    public function participants(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $room = $this->resolveRoom($onlineClass);
        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        $roster = $this->participantService->getRoster($room);

        return $this->ok([
            'participants' => $roster,
            'total' => count($roster),
        ]);
    }

    /**
     * POST /api/live-classes/{id}/participants/{userId}/mute
     */
    public function muteParticipant(OnlineClass $onlineClass, int $userId, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room || !$user->isAdministrator() && $room->host_user_id !== $user->id) {
            return $this->fail('Unauthorized.', 403);
        }

        $targetUser = User::findOrFail($userId);
        $mediaType = $request->input('media_type', 'microphone');

        $muted = $this->participantService->muteParticipant($room, $targetUser, $mediaType);

        if ($muted) {
            // Record event
            $room->events()->create([
                'type' => 'participant_muted',
                'user_id' => $user->id,
                'description' => "{$user->name} muted {$targetUser->name}'s {$mediaType}",
                'meta' => ['target_user_id' => $userId, 'media_type' => $mediaType],
            ]);

            return $this->ok([], "Participant muted.");
        }

        return $this->fail('Failed to mute participant.', 500);
    }

    /**
     * POST /api/live-classes/{id}/participants/{userId}/remove
     */
    public function removeParticipant(OnlineClass $onlineClass, int $userId, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room || (!$user->isAdministrator() && $room->host_user_id !== $user->id)) {
            return $this->fail('Unauthorized.', 403);
        }

        $targetUser = User::findOrFail($userId);
        $removed = $this->participantService->removeParticipant($room, $targetUser);

        if ($removed) {
            $room->events()->create([
                'type' => 'participant_removed',
                'user_id' => $user->id,
                'description' => "{$user->name} removed {$targetUser->name}",
                'meta' => ['target_user_id' => $userId],
            ]);

            return $this->ok([], "Participant removed.");
        }

        return $this->fail('Failed to remove participant.', 500);
    }

    /**
     * POST /api/live-classes/{id}/participants/{userId}/permissions
     */
    public function updatePermissions(OnlineClass $onlineClass, int $userId, UpdateParticipantPermissionRequest $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room || (!$user->isAdministrator() && $room->host_user_id !== $user->id)) {
            return $this->fail('Unauthorized.', 403);
        }

        $targetUser = User::findOrFail($userId);
        $identity = $this->liveKit->buildIdentity($room, $targetUser);

        $canPublish = $request->boolean('can_use_camera', true);
        $canPublishSources = [];
        if ($canPublish) {
            $canPublishSources[] = 'camera';
        }
        if ($request->boolean('can_speak', true)) {
            $canPublishSources[] = 'microphone';
        }
        if ($request->boolean('can_share_screen', false)) {
            $canPublishSources[] = 'screen_share';
            $canPublishSources[] = 'screen_share_audio';
        }

        $updated = $this->liveKit->updateParticipantPermissions(
            $room->livekit_room_name,
            $identity,
            canPublish: $canPublish,
            canPublishData: true,
            canPublishSources: $canPublishSources,
        );

        // Update local permissions record
        DB::table('live_class_permissions')->updateOrInsert(
            ['class_room_id' => $room->id, 'user_id' => $userId],
            $request->only('can_speak', 'can_use_camera', 'can_share_screen', 'can_chat', 'can_record') + [
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $room->events()->create([
            'type' => 'settings_changed',
            'user_id' => $user->id,
            'description' => "{$user->name} updated {$targetUser->name}'s permissions",
            'meta' => ['target_user_id' => $userId, 'permissions' => $request->validated()],
        ]);

        return $this->ok([], "Permissions updated.");
    }

    // ─── Hand Raise ────────────────────────────────────────────────

    /**
     * POST /api/live-classes/{id}/raise-hand
     */
    public function raiseHand(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room || $room->state !== RoomState::Live) {
            return $this->fail('Class is not live.', 422);
        }

        $participant = $room->participants()->where('user_id', $user->id)->first();
        if (!$participant) {
            return $this->fail('You are not in this class.', 422);
        }

        $participant->update(['is_hand_raised' => true]);

        // Update LiveKit metadata
        $identity = $this->liveKit->buildIdentity($room, $user);
        $this->liveKit->updateParticipantMetadata($room->livekit_room_name, $identity, [
            'user_id' => $user->id,
            'name' => $user->name,
            'role' => $participant->role->value,
            'hand_raised' => true,
        ]);

        // Record event
        $room->events()->create([
            'type' => 'hand_raised',
            'user_id' => $user->id,
            'description' => "{$user->name} raised their hand",
        ]);

        // Send data message to all participants
        $this->liveKit->sendDataMessage($room->livekit_room_name, [
            'type' => 'hand_raised',
            'user_id' => $user->id,
            'name' => $user->name,
        ], 'hand-raise');

        return $this->ok([], "Hand raised.");
    }

    /**
     * POST /api/live-classes/{id}/lower-hand
     */
    public function lowerHand(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        $participant = $room->participants()->where('user_id', $user->id)->first();
        if (!$participant) {
            return $this->fail('You are not in this class.', 422);
        }

        $participant->update(['is_hand_raised' => false]);

        $identity = $this->liveKit->buildIdentity($room, $user);
        $this->liveKit->updateParticipantMetadata($room->livekit_room_name, $identity, [
            'user_id' => $user->id,
            'name' => $user->name,
            'role' => $participant->role->value,
            'hand_raised' => false,
        ]);

        $this->liveKit->sendDataMessage($room->livekit_room_name, [
            'type' => 'hand_lowered',
            'user_id' => $user->id,
        ], 'hand-raise');

        return $this->ok([], "Hand lowered.");
    }

    // ─── Chat ──────────────────────────────────────────────────────

    /**
     * GET /api/live-classes/{id}/chat
     */
    public function chat(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $room = $this->resolveRoom($onlineClass);
        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        $afterId = $request->input('after');
        $query = $room->messages()->with('user')->orderBy('id', 'desc')->limit(100);

        if ($afterId) {
            $query->where('id', '>', $afterId);
        }

        $messages = $query->get()->reverse()->values();

        return $this->ok([
            'messages' => $messages,
            'has_more' => $messages->count() === 100,
        ]);
    }

    /**
     * POST /api/live-classes/{id}/chat
     */
    public function sendChat(OnlineClass $onlineClass, SendLiveClassMessageRequest $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        if (!$this->policy('sendMessage', $room)) {
            return $this->fail('Chat is disabled.', 403);
        }

        $type = $request->input('type', 'message');
        $isAnnouncement = $type === 'announcement';

        // Only instructor/admin can make announcements
        if ($isAnnouncement && !$user->isAdministrator() && $room->host_user_id !== $user->id) {
            return $this->fail('Only instructors can send announcements.', 403);
        }

        // Persist message
        $message = $room->messages()->create([
            'user_id' => $user->id,
            'body' => $request->input('message'),
            'type' => $isAnnouncement ? 'announcement' : 'message',
        ]);

        // Record event
        $room->events()->create([
            'type' => 'chat_message',
            'user_id' => $user->id,
            'description' => $isAnnouncement ? "Announcement by {$user->name}" : null,
        ]);

        // Broadcast via LiveKit data channel
        $this->liveKit->sendDataMessage($room->livekit_room_name, [
            'type' => 'chat_message',
            'message' => [
                'id' => $message->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'body' => $message->body,
                'message_type' => $message->type,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 'chat');

        return $this->ok([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'type' => $message->type,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 'Message sent.');
    }

    // ─── Recording ─────────────────────────────────────────────────

    /**
     * POST /api/live-classes/{id}/recording/start
     */
    public function startRecording(OnlineClass $onlineClass, StartRecordingRequest $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room || !$user->isAdministrator() && $room->host_user_id !== $user->id) {
            return $this->fail('Unauthorized.', 403);
        }

        if ($room->active_egress_id) {
            return $this->fail('Recording is already in progress.', 422);
        }

        $layout = $request->input('layout', config('livekit.recording.layout', 'speaker'));

        $result = $this->liveKit->startRecording($room, $layout);

        if (!$result) {
            return $this->fail('Failed to start recording.', 500);
        }

        // Create recording record
        $recording = $room->recordings()->create([
            'live_class_id' => $onlineClass->id,
            'egress_id' => $result['egress_id'],
            'room_name' => $result['room_name'],
            'status' => 'processing',
            'title' => "Class Recording - {$onlineClass->title}",
            'started_at' => now(),
            'created_by' => $user->id,
        ]);

        $room->update(['active_egress_id' => $result['egress_id']]);

        // Record event
        $room->events()->create([
            'type' => 'recording_started',
            'user_id' => $user->id,
            'description' => "{$user->name} started recording",
        ]);

        // Broadcast
        $this->liveKit->sendDataMessage($room->livekit_room_name, [
            'type' => 'recording_started',
            'recorded_by' => $user->name,
        ], 'recording');

        return $this->ok([
            'recording' => [
                'id' => $recording->id,
                'egress_id' => $result['egress_id'],
                'status' => 'processing',
            ],
        ], 'Recording started.');
    }

    /**
     * POST /api/live-classes/{id}/recording/stop
     */
    public function stopRecording(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room || (!$user->isAdministrator() && $room->host_user_id !== $user->id)) {
            return $this->fail('Unauthorized.', 403);
        }

        if (!$room->active_egress_id) {
            return $this->fail('No active recording.', 422);
        }

        $this->liveKit->stopRecording($room->active_egress_id);

        $room->update(['active_egress_id' => null]);

        // Record event
        $room->events()->create([
            'type' => 'recording_stopped',
            'user_id' => $user->id,
            'description' => "{$user->name} stopped recording",
        ]);

        // Broadcast
        $this->liveKit->sendDataMessage($room->livekit_room_name, [
            'type' => 'recording_stopped',
        ], 'recording');

        return $this->ok([], 'Recording stopped.');
    }

    /**
     * GET /api/live-classes/{id}/recordings
     */
    public function recordings(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $room = $this->resolveRoom($onlineClass);
        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        $recordings = $room->recordings()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'status' => $r->status,
                'duration_seconds' => $r->duration_seconds,
                'file_url' => $r->file_url,
                'thumbnail_url' => $r->thumbnail_url,
                'is_published' => $r->is_published,
                'view_count' => $r->view_count,
                'created_at' => $r->created_at->toISOString(),
            ]);

        return $this->ok(['recordings' => $recordings]);
    }

    // ─── Attendance ────────────────────────────────────────────────

    /**
     * GET /api/live-classes/{id}/attendance
     */
    public function attendance(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $room = $this->resolveRoom($onlineClass);
        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        $stats = $this->attendanceService->getAttendanceStats($room);

        return $this->ok($stats);
    }

    // ─── State / Bootstrap ─────────────────────────────────────────

    /**
     * GET /api/live-classes/{id}/state
     * Get current room state for polling/fallback.
     */
    public function state(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $room = $this->resolveRoom($onlineClass);
        if (!$room) {
            return $this->fail('Class room not found.', 404);
        }

        // Sync participant count from LiveKit
        if ($room->livekit_room_name && $room->state === RoomState::Live) {
            $this->liveKit->syncParticipantCount($room);
        }

        $recentMessages = $room->messages()
            ->with('user')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return $this->ok([
            'room' => [
                'id' => $room->id,
                'state' => $room->state->value,
                'started_at' => $room->started_at?->toISOString(),
                'ended_at' => $room->ended_at?->toISOString(),
                'settings' => $room->settings,
                'is_locked' => $room->setting('is_locked', false),
                'participant_count' => $room->livekit_participant_count,
                'peak_participant_count' => $room->peak_participant_count,
                'active_egress_id' => $room->active_egress_id,
            ],
            'messages' => $recentMessages,
        ]);
    }

    // ─── Settings ──────────────────────────────────────────────────

    /**
     * PUT /api/live-classes/{id}/settings
     */
    public function updateSettings(OnlineClass $onlineClass, Request $request): JsonResponse
    {
        $user = $request->user();
        $room = $this->resolveRoom($onlineClass);

        if (!$room || (!$user->isAdministrator() && $room->host_user_id !== $user->id)) {
            return $this->fail('Unauthorized.', 403);
        }

        $validated = $request->validate([
            'chat_enabled' => 'nullable|boolean',
            'announcements_enabled' => 'nullable|boolean',
            'materials_visible' => 'nullable|boolean',
            'recordings_visible' => 'nullable|boolean',
            'assignments_visible' => 'nullable|boolean',
            'is_locked' => 'nullable|boolean',
        ]);

        $settings = array_merge($room->settings ?? [], array_filter($validated, fn($v) => $v !== null));
        $room->update(['settings' => $settings]);

        // Update LiveKit room metadata
        $this->liveKit->updateRoomMetadata($room->livekit_room_name, [
            'settings' => $settings,
        ]);

        $room->events()->create([
            'type' => 'settings_changed',
            'user_id' => $user->id,
            'description' => "{$user->name} updated room settings",
            'meta' => ['settings' => $validated],
        ]);

        return $this->ok(['settings' => $settings], 'Settings updated.');
    }

    // ─── Helpers ───────────────────────────────────────────────────

    protected function resolveRoom(OnlineClass $onlineClass): ?ClassRoom
    {
        return ClassRoom::where('subject_type', OnlineClass::class)
            ->where('subject_id', $onlineClass->id)
            ->first();
    }

    protected function resolveRole(User $user, OnlineClass $onlineClass, ClassRoom $room): RoomRole
    {
        if ($user->isAdministrator()) {
            return RoomRole::Host;
        }

        if ($room->host_user_id === $user->id) {
            return RoomRole::Host;
        }

        // Check if user is the assigned teacher
        if ($onlineClass->teacher_id && $user->trainer && $user->trainer->id === $onlineClass->teacher_id) {
            return RoomRole::Host;
        }

        return RoomRole::Participant;
    }

    protected function resolvePermissions(ClassRoom $room, User $user, RoomRole $role): array
    {
        if (in_array($role->value, ['host', 'moderator'])) {
            return config('livekit.permissions.instructor');
        }

        // Check custom permissions
        $custom = DB::table('live_class_permissions')
            ->where('class_room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        if ($custom) {
            return [
                'can_publish' => (bool) $custom->can_use_camera || $custom->can_speak,
                'can_publish_data' => (bool) $custom->can_chat,
                'can_publish_sources' => array_filter([
                    $custom->can_use_camera ? 'camera' : null,
                    $custom->can_speak ? 'microphone' : null,
                    $custom->can_share_screen ? 'screen_share' : null,
                    $custom->can_share_screen ? 'screen_share_audio' : null,
                ]),
                'can_subscribe' => true,
                'can_update_own_metadata' => true,
            ];
        }

        return config('livekit.permissions.student');
    }

    protected function registerParticipant(ClassRoom $room, User $user, RoomRole $role): ClassRoomParticipant
    {
        $identity = $this->liveKit->buildIdentity($room, $user);

        $participant = $room->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'livekit_identity' => $identity,
                'left_at' => null,
                'last_seen_at' => now(),
                'join_count' => $participant->join_count + 1,
            ]);
        } else {
            $participant = $room->participants()->create([
                'user_id' => $user->id,
                'role' => $role,
                'display_name' => $user->name,
                'livekit_identity' => $identity,
                'joined_at' => now(),
                'last_seen_at' => now(),
                'join_count' => 1,
            ]);

            $room->increment('participant_count');
            $room->update(['peak_participant_count' => max($room->peak_participant_count, $room->participant_count)]);
        }

        return $participant;
    }

    protected function policy(string $ability, ClassRoom $room): bool
    {
        $policy = app(\App\Policies\LiveClassPolicy::class);
        $user = Auth::user();
        return $policy->{$ability}($user, $room);
    }

    protected function ok(mixed $data = null, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function fail(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
