<?php

namespace App\Http\Controllers\Api;

use App\Domains\OnlineLearning\Services\LiveKitService;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Models\ClassRoomRecording;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveKitWebhookController extends Controller
{
    public function __construct(
        protected LiveKitService $liveKit,
    ) {}

    /**
     * POST /api/webhooks/livekit
     * Handle LiveKit server webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        $body = $request->getContent();
        $authHeader = $request->header('Authorization');

        $event = $this->liveKit->processWebhook($body, $authHeader);

        if (!$event) {
            Log::warning('Invalid LiveKit webhook');
            return response()->json(['error' => 'Invalid webhook'], 400);
        }

        try {
            $this->processEvent($event);
        } catch (\Exception $e) {
            Log::error('Failed to process LiveKit webhook', [
                'event' => $event->getEvent(),
                'error' => $e->getMessage(),
            ]);
        }

        // LiveKit expects 200 OK
        return response()->json(['ok' => true]);
    }

    protected function processEvent($event): void
    {
        match ($event->getEvent()) {
            'room_started' => $this->handleRoomStarted($event),
            'room_finished' => $this->handleRoomFinished($event),
            'participant_joined' => $this->handleParticipantJoined($event),
            'participant_left' => $this->handleParticipantLeft($event),
            'egress_started' => $this->handleEgressStarted($event),
            'egress_ended' => $this->handleEgressEnded($event),
            default => Log::info('Unhandled LiveKit event', ['event' => $event->getEvent()]),
        };
    }

    protected function handleRoomStarted($event): void
    {
        $roomName = $event->getRoom()?->getName();
        if (!$roomName) return;

        $room = ClassRoom::where('livekit_room_name', $roomName)->first();
        if (!$room) return;

        Log::info('LiveKit room started', ['room_id' => $room->id, 'room_name' => $roomName]);
    }

    protected function handleRoomFinished($event): void
    {
        $roomName = $event->getRoom()?->getName();
        if (!$roomName) return;

        $room = ClassRoom::where('livekit_room_name', $roomName)->first();
        if (!$room) return;

        // Auto-end the room if it's still live
        if ($room->state->value === 'live') {
            $room->update([
                'state' => 'ended',
                'ended_at' => now(),
                'active_egress_id' => null,
            ]);

            $room->events()->create([
                'type' => 'room_ended',
                'description' => 'Room closed automatically (LiveKit room finished)',
            ]);

            // Update the underlying online class
            if ($room->subject_type === 'App\\Models\\OnlineClass' && $room->subject) {
                $room->subject->update([
                    'status' => 'completed',
                    'actual_end_at' => now(),
                ]);
            }

            Log::info('Room auto-ended via webhook', ['room_id' => $room->id]);
        }
    }

    protected function handleParticipantJoined($event): void
    {
        $roomName = $event->getRoom()?->getName();
        $participantInfo = $event->getParticipant();

        if (!$roomName || !$participantInfo) return;

        $room = ClassRoom::where('livekit_room_name', $roomName)->first();
        if (!$room) return;

        $identity = $participantInfo->getIdentity();

        // Extract user_id from identity
        if (preg_match('/room_\d+_user_(\d+)/', $identity, $matches)) {
            $userId = (int) $matches[1];

            $participant = $room->participants()->where('user_id', $userId)->first();
            if ($participant) {
                $participant->update([
                    'livekit_participant_sid' => $participantInfo->getSid(),
                    'last_seen_at' => now(),
                ]);
            }

            Log::info('Participant joined via webhook', [
                'room_id' => $room->id,
                'user_id' => $userId,
            ]);
        }

        $room->increment('livekit_participant_count');
    }

    protected function handleParticipantLeft($event): void
    {
        $roomName = $event->getRoom()?->getName();
        $participantInfo = $event->getParticipant();

        if (!$roomName || !$participantInfo) return;

        $room = ClassRoom::where('livekit_room_name', $roomName)->first();
        if (!$room) return;

        $identity = $participantInfo->getIdentity();

        if (preg_match('/room_\d+_user_(\d+)/', $identity, $matches)) {
            $userId = (int) $matches[1];

            $participant = $room->participants()->where('user_id', $userId)->first();
            if ($participant) {
                $participant->update(['left_at' => now()]);
            }

            Log::info('Participant left via webhook', [
                'room_id' => $room->id,
                'user_id' => $userId,
            ]);
        }

        if ($room->livekit_participant_count > 0) {
            $room->decrement('livekit_participant_count');
        }
    }

    protected function handleEgressStarted($event): void
    {
        $egressInfo = $event->getEgressInfo();
        if (!$egressInfo) return;

        $egressId = $egressInfo->getEgressId();
        $roomName = $egressInfo->getRoomName();

        Log::info('Egress started', ['egress_id' => $egressId, 'room' => $roomName]);

        $room = ClassRoom::where('livekit_room_name', $roomName)->first();
        if ($room) {
            $recording = $room->recordings()->where('egress_id', $egressId)->first();
            if ($recording) {
                $recording->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);
            }
        }
    }

    protected function handleEgressEnded($event): void
    {
        $egressInfo = $event->getEgressInfo();
        if (!$egressInfo) return;

        $egressId = $egressInfo->getEgressId();
        $roomName = $egressInfo->getRoomName();
        $status = $egressInfo->getStatus()->name ?? 'unknown';

        Log::info('Egress ended', [
            'egress_id' => $egressId,
            'room' => $roomName,
            'status' => $status,
        ]);

        $room = ClassRoom::where('livekit_room_name', $roomName)->first();
        if (!$room) return;

        $recording = $room->recordings()->where('egress_id', $egressId)->first();
        if (!$recording) return;

        // Extract file info
        $fileResults = $egressInfo->getFileResultsList();
        $fileUrl = null;
        $fileSize = null;

        if (count($fileResults) > 0) {
            $fileResult = $fileResults[0];
            $fileUrl = $fileResult->getUrl() ?? $fileResult->getFilepath();
            $fileSize = $fileResult->GetSize() ?? null;
        }

        $duration = (int) ($egressInfo->getDuration() / 1000); // ms to seconds

        $recording->update([
            'status' => $status === 'EGRESS_COMPLETE' ? 'ready' : 'failed',
            'ended_at' => now(),
            'duration_seconds' => $duration,
            'file_url' => $fileUrl,
            'file_size' => $fileSize,
        ]);

        // Clear active egress if this was the one
        if ($room->active_egress_id === $egressId) {
            $room->update(['active_egress_id' => null]);
        }

        Log::info('Recording status updated', [
            'recording_id' => $recording->id,
            'status' => $recording->status,
            'duration' => $duration,
        ]);
    }
}
