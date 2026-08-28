<?php

namespace App\Domains\OnlineLearning\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\EgressServiceClient;
use Agence104\LiveKit\EncodedOutputs;
use Agence104\LiveKit\RoomCreateOptions;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\VideoGrant;
use Agence104\LiveKit\WebhookReceiver;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Domains\OnlineLearning\Room\Enums\RoomState;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Models\ClassRoomParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Livekit\WebhookEvent;

class LiveKitService
{
    protected RoomServiceClient $roomClient;
    protected ?EgressServiceClient $egressClient;
    protected ?WebhookReceiver $webhookReceiver;

    public function __construct()
    {
        $apiKey = config('livekit.api_key');
        $apiSecret = config('livekit.api_secret');
        $url = config('livekit.url');

        if (!$apiKey || !$apiSecret) {
            throw new \RuntimeException('LiveKit credentials not configured. Set LIVEKIT_API_KEY and LIVEKIT_API_SECRET in .env');
        }

        // The REST API needs http(s):// while the WebSocket client uses wss://
        $apiUrl = preg_replace('#^wss://#', 'https://', $url);
        $this->roomClient = new RoomServiceClient($apiUrl, $apiKey, $apiSecret);

        if (config('livekit.recording.enabled')) {
            $this->egressClient = new EgressServiceClient($apiUrl, $apiKey, $apiSecret);
        }

        $webhookSecret = config('livekit.webhook_secret');
        if ($webhookSecret) {
            $this->webhookReceiver = new WebhookReceiver($apiKey, $webhookSecret);
        }
    }

    // ─── Token Generation ──────────────────────────────────────────

    /**
     * Generate a LiveKit access token for a participant.
     */
    public function generateToken(
        ClassRoom $room,
        User $user,
        string $role,
        array $customPermissions = [],
    ): string {
        $identity = $this->buildIdentity($room, $user);

        $tokenOptions = (new AccessTokenOptions())
            ->setIdentity($identity)
            ->setTtl(config('livekit.token_ttl', 14400))
            ->setName($user->name)
            ->setMetadata(json_encode([
                'user_id' => $user->id,
                'role' => $role,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ]));

        $token = (new AccessToken(
            config('livekit.api_key'),
            config('livekit.api_secret')
        ))->init($tokenOptions);

        // Build VideoGrant
        $grant = $this->buildGrant($room, $role, $customPermissions);
        $token->setGrant($grant);

        return $token->toJwt();
    }

    /**
     * Build identity string for a user in a room.
     */
    public function buildIdentity(ClassRoom $room, User $user): string
    {
        return "room_{$room->id}_user_{$user->id}";
    }

    /**
     * Build VideoGrant based on role and permissions.
     */
    protected function buildGrant(ClassRoom $room, string $role, array $customPermissions = []): VideoGrant
    {
        $livekitRoomName = $room->livekit_room_name ?? $this->generateRoomName($room);
        $defaultPerms = config("livekit.permissions.{$role}", config('livekit.permissions.student'));

        // Merge custom permissions
        $perms = array_merge($defaultPerms, $customPermissions);

        $grant = (new VideoGrant())
            ->setRoomName($livekitRoomName)
            ->setRoomJoin(true)
            ->setCanPublish((bool) ($perms['can_publish'] ?? false))
            ->setCanSubscribe(true)
            ->setCanPublishData((bool) ($perms['can_publish_data'] ?? true))
            ->setCanUpdateOwnMetadata((bool) ($perms['can_update_own_metadata'] ?? true));

        if (!empty($perms['can_publish_sources'])) {
            $grant->setCanPublishSources($perms['can_publish_sources']);
        }

        // Instructor/moderator get admin privileges
        if (in_array($role, ['instructor', 'moderator', 'host'])) {
            $grant->setRoomAdmin(true);
            $grant->setRoomRecord(true);
        }

        return $grant;
    }

    // ─── Room Management ───────────────────────────────────────────

    /**
     * Generate a unique LiveKit room name.
     */
    public function generateRoomName(ClassRoom $room): string
    {
        $prefix = config('livekit.room_prefix', 'class');
        $hash = substr(md5("room_{$room->id}_" . $room->room_key), 0, 8);
        return "{$prefix}_{$room->id}_{$hash}";
    }

    /**
     * Ensure LiveKit room exists and is active.
     */
    public function ensureRoom(ClassRoom $room): ClassRoom
    {
        if ($room->livekit_room_active && $room->livekit_room_name) {
            return $room;
        }

        $roomName = $this->generateRoomName($room);

        try {
            // Try to get existing room first
            $existingRooms = $this->roomClient->listRooms([$roomName]);
            $lkRoom = $existingRooms->getRooms()[0] ?? null;
        } catch (\Exception $e) {
            // Room doesn't exist, create it
            $lkRoom = null;
        }

        if (!$lkRoom) {
            $options = (new RoomCreateOptions())
                ->setName($roomName)
                ->setEmptyTimeout(config('livekit.room_defaults.empty_timeout', 300))
                ->setMaxParticipants(config('livekit.room_defaults.max_participants', 200));

            $lkRoom = $this->roomClient->createRoom($options);

            Log::info('LiveKit room created', [
                'room_id' => $room->id,
                'livekit_room' => $roomName,
                'sid' => $lkRoom->getSid(),
            ]);
        }

        // Update class_rooms record
        $room->update([
            'livekit_room_name' => $roomName,
            'livekit_room_sid' => $lkRoom->getSid(),
            'livekit_room_active' => true,
        ]);

        return $room->fresh();
    }

    /**
     * Get LiveKit room info.
     */
    public function getRoomInfo(string $roomName): ?object
    {
        try {
            $response = $this->roomClient->listRooms([$roomName]);
            $rooms = $response->getRooms();
            return $rooms[0] ?? null;
        } catch (\Exception $e) {
            Log::warning('Failed to get LiveKit room info', ['room' => $roomName, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Delete a LiveKit room.
     */
    public function deleteRoom(string $roomName): bool
    {
        try {
            $this->roomClient->deleteRoom($roomName);
            Log::info('LiveKit room deleted', ['room' => $roomName]);
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to delete LiveKit room', ['room' => $roomName, 'error' => $e->getMessage()]);
            return false;
        }
    }

    // ─── Participant Management ────────────────────────────────────

    /**
     * List all participants in a LiveKit room.
     */
    public function listParticipants(string $roomName): array
    {
        try {
            $response = $this->roomClient->listParticipants($roomName);
            return iterator_to_array($response->getParticipants()) ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to list LiveKit participants', ['room' => $roomName, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Remove a participant from the room.
     */
    public function removeParticipant(string $roomName, string $identity): bool
    {
        try {
            $this->roomClient->removeParticipant($roomName, $identity);
            Log::info('Participant removed from LiveKit', ['room' => $roomName, 'identity' => $identity]);
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to remove participant', ['room' => $roomName, 'identity' => $identity, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Mute a participant's track.
     */
    public function muteTrack(string $roomName, string $identity, string $trackSid, bool $muted = true): bool
    {
        try {
            $this->roomClient->mutePublishedTrack($roomName, $identity, $trackSid, $muted);
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to mute track', ['room' => $roomName, 'track' => $trackSid, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Update participant permissions in LiveKit.
     */
    public function updateParticipantPermissions(
        string $roomName,
        string $identity,
        bool $canPublish = false,
        bool $canSubscribe = true,
        bool $canPublishData = true,
        array $canPublishSources = [],
    ): bool {
        try {
            $permission = new \Livekit\ParticipantPermission([
                'can_publish' => $canPublish,
                'can_subscribe' => $canSubscribe,
                'can_publish_data' => $canPublishData,
                'can_publish_sources' => $canPublishSources,
            ]);

            $this->roomClient->updateParticipant(
                $roomName,
                $identity,
                permission: $permission,
            );
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to update participant permissions', [
                'room' => $roomName,
                'identity' => $identity,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update participant metadata (e.g. hand raised state).
     */
    public function updateParticipantMetadata(string $roomName, string $identity, array $metadata): bool
    {
        try {
            $this->roomClient->updateParticipant(
                $roomName,
                $identity,
                metadata: json_encode($metadata),
            );
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to update participant metadata', [
                'room' => $roomName,
                'identity' => $identity,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update room metadata (e.g. class state).
     */
    public function updateRoomMetadata(string $roomName, array $metadata): bool
    {
        try {
            $this->roomClient->updateRoomMetadata($roomName, json_encode($metadata));
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to update room metadata', ['room' => $roomName, 'error' => $e->getMessage()]);
            return false;
        }
    }

    // ─── Recording ─────────────────────────────────────────────────

    /**
     * Start room composite recording.
     */
    public function startRecording(
        ClassRoom $room,
        string $layout = 'speaker',
        ?string $outputPath = null,
    ): ?array {
        if (!config('livekit.recording.enabled') || !$this->egressClient) {
            Log::warning('Recording not enabled or egress client not available');
            return null;
        }

        $roomName = $room->livekit_room_name;
        if (!$roomName) {
            Log::warning('Cannot start recording: LiveKit room name not set', ['room_id' => $room->id]);
            return null;
        }

        try {
            $disk = config('livekit.recording.storage_disk', 'public');
            $directory = config('livekit.recording.recording_directory', 'live-class-recordings');
            $filename = $outputPath ?? "{$directory}/room_{$room->id}_" . date('Y-m-d_His') . '.mp4';

            $fileOutput = new \Livekit\EncodedFileOutput([
                'filepath' => $filename,
                'display_name' => "Class Recording - {$room->id}",
            ]);

            $egressInfo = $this->egressClient->startRoomCompositeEgress(
                $roomName,
                $layout,
                $fileOutput,
            );

            $egressId = $egressInfo->getEgressId();

            Log::info('Recording started', [
                'room_id' => $room->id,
                'egress_id' => $egressId,
                'room' => $roomName,
            ]);

            return [
                'egress_id' => $egressId,
                'status' => $egressInfo->getStatus(),
                'room_name' => $roomName,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to start recording', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Stop an active recording.
     */
    public function stopRecording(string $egressId): ?object
    {
        if (!$this->egressClient) {
            return null;
        }

        try {
            $info = $this->egressClient->stopEgress($egressId);
            Log::info('Recording stopped', ['egress_id' => $egressId]);
            return $info;
        } catch (\Exception $e) {
            Log::warning('Failed to stop recording', ['egress_id' => $egressId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * List active recordings for a room.
     */
    public function listRecordings(string $roomName): array
    {
        if (!$this->egressClient) {
            return [];
        }

        try {
            $response = $this->egressClient->listEgress($roomName, '', true);
            return $response->getItemsList() ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to list recordings', ['room' => $roomName, 'error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── Webhook Processing ────────────────────────────────────────

    /**
     * Process a LiveKit webhook event.
     */
    public function processWebhook(string $body, ?string $authHeader): ?WebhookEvent
    {
        if (!$this->webhookReceiver) {
            Log::warning('Webhook receiver not configured');
            return null;
        }

        try {
            $event = $this->webhookReceiver->receive($body, $authHeader);

            Log::info('LiveKit webhook received', [
                'event' => get_class($event),
                'event_type' => $event->getEvent(),
            ]);

            return $event;
        } catch (\Exception $e) {
            Log::error('LiveKit webhook processing failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ─── Data Messages ─────────────────────────────────────────────

    /**
     * Send a data message to all participants in a room.
     */
    public function sendDataMessage(
        string $roomName,
        array $payload,
        string $topic = 'chat',
        array $destinationIdentities = [],
    ): bool {
        try {
            $data = json_encode($payload);
            $kind = 0; // RELIABLE
            $this->roomClient->sendData($roomName, $data, $kind, $destinationIdentities, $topic);
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to send data message', ['room' => $roomName, 'error' => $e->getMessage()]);
            return false;
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────

    /**
     * Resolve a LiveKit room name back to a ClassRoom model.
     */
    public function resolveRoomByLiveKitName(string $livekitRoomName): ?ClassRoom
    {
        return ClassRoom::where('livekit_room_name', $livekitRoomName)->first();
    }

    /**
     * Sync participant count from LiveKit to database.
     */
    public function syncParticipantCount(ClassRoom $room): int
    {
        if (!$room->livekit_room_name) {
            return 0;
        }

        $participants = $this->listParticipants($room->livekit_room_name);
        $count = count($participants);

        $room->update(['livekit_participant_count' => $count]);

        return $count;
    }

    /**
     * Check if LiveKit is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty(config('livekit.api_key'))
            && !empty(config('livekit.api_secret'))
            && !empty(config('livekit.url'));
    }
}
