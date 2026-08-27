<?php

namespace App\Domains\OnlineLearning\Services;

use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Models\ClassRoomParticipant;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LiveClassParticipantService
{
    protected LiveKitService $liveKitService;

    public function __construct(LiveKitService $liveKitService)
    {
        $this->liveKitService = $liveKitService;
    }

    /**
     * Allow a student into the room (move from waiting to live).
     */
    public function admitParticipant(ClassRoom $room, User $user): ?ClassRoomParticipant
    {
        $participant = $room->participants()
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return null;
        }

        // Update LiveKit permissions to allow publishing
        $identity = $this->liveKitService->buildIdentity($room, $user);
        $this->liveKitService->updateParticipantPermissions(
            $room->livekit_room_name,
            $identity,
            canPublish: true,
            canPublishData: true,
        );

        Log::info('Participant admitted to room', [
            'room_id' => $room->id,
            'user_id' => $user->id,
        ]);

        return $participant;
    }

    /**
     * Mute a student in the room.
     */
    public function muteParticipant(ClassRoom $room, User $targetUser, string $mediaType = 'microphone'): bool
    {
        $identity = $this->liveKitService->buildIdentity($room, $targetUser);
        $participants = $this->liveKitService->listParticipants($room->livekit_room_name);

        foreach ($participants as $lkParticipant) {
            if ($lkParticipant->getIdentity() === $identity) {
                $tracks = $lkParticipant->getTracksList();
                foreach ($tracks as $track) {
                    $trackSource = $track->getSource();
                    if (
                        ($mediaType === 'microphone' && $trackSource === 1) || // MICROPHONE
                        ($mediaType === 'camera' && $trackSource === 2) ||     // CAMERA
                        ($mediaType === 'screen_share' && $trackSource === 3)   // SCREEN_SHARE
                    ) {
                        $this->liveKitService->muteTrack(
                            $room->livekit_room_name,
                            $identity,
                            $track->getSid(),
                            true
                        );
                    }
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a participant from the room.
     */
    public function removeParticipant(ClassRoom $room, User $targetUser): bool
    {
        $identity = $this->liveKitService->buildIdentity($room, $targetUser);

        // Remove from LiveKit
        $removed = $this->liveKitService->removeParticipant($room->livekit_room_name, $identity);

        if ($removed) {
            // Update database
            $participant = $room->participants()
                ->where('user_id', $targetUser->id)
                ->first();

            if ($participant) {
                $participant->update([
                    'left_at' => now(),
                ]);
            }

            Log::info('Participant removed', [
                'room_id' => $room->id,
                'user_id' => $targetUser->id,
            ]);
        }

        return $removed;
    }

    /**
     * Get full roster with LiveKit sync.
     */
    public function getRoster(ClassRoom $room): array
    {
        $dbParticipants = $room->participants()
            ->with('user')
            ->get()
            ->keyBy('user_id');

        $livekitParticipants = $room->livekit_room_name
            ? $this->liveKitService->listParticipants($room->livekit_room_name)
            : [];

        $roster = [];
        foreach ($livekitParticipants as $lkParticipant) {
            $identity = $lkParticipant->getIdentity();
            // Extract user_id from identity: room_{roomId}_user_{userId}
            if (preg_match('/room_\d+_user_(\d+)/', $identity, $matches)) {
                $userId = (int) $matches[1];
                $dbParticipant = $dbParticipants->get($userId);

                $metadata = json_decode($lkParticipant->getMetadata() ?? '{}', true) ?: [];

                $roster[] = [
                    'user_id' => $userId,
                    'name' => $metadata['name'] ?? $lkParticipant->getName() ?? $dbParticipant?->display_name ?? 'Unknown',
                    'role' => $metadata['role'] ?? $dbParticipant?->role->value ?? 'participant',
                    'identity' => $identity,
                    'participant_sid' => $lkParticipant->getSid(),
                    'is_connected' => $lkParticipant->getState() === 1, // ACTIVE
                    'is_publisher' => $lkParticipant->getPublisher(),
                    'tracks' => array_map(fn($t) => [
                        'sid' => $t->getSid(),
                        'source' => $t->getSource(),
                        'muted' => $t->getMuted(),
                    ], $lkParticipant->getTracksList()),
                    'db_participant_id' => $dbParticipant?->id,
                    'is_hand_raised' => $dbParticipant?->is_hand_raised ?? false,
                    'is_camera_enabled' => $dbParticipant?->is_camera_enabled ?? false,
                    'is_microphone_enabled' => $dbParticipant?->is_microphone_enabled ?? false,
                    'is_screen_sharing' => $dbParticipant?->is_screen_sharing ?? false,
                    'joined_at' => $dbParticipant?->joined_at?->toISOString(),
                    'total_seconds' => $dbParticipant?->total_seconds ?? 0,
                ];
            }
        }

        return $roster;
    }
}
