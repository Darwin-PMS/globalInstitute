<?php

namespace App\Http\Controllers\Api\OnlineLearning\Room;

use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectInterface;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Services\ClassRoomService;
use App\Domains\OnlineLearning\Room\Services\RoomAccessService;
use App\Domains\OnlineLearning\Room\Services\RoomMessageService;
use App\Domains\OnlineLearning\Room\Services\RoomPresenceService;
use App\Domains\OnlineLearning\Room\Services\RoomTimelineService;
use App\Http\Controllers\Api\OnlineLearning\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Shared plumbing for every room endpoint.
 *
 * Each request resolves the room's subject, brings the room's own clock up to
 * date, and derives the caller's role from the session — so no endpoint has to
 * remember to authorise, and none of them trust the client for identity.
 */
abstract class RoomController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ClassRoomService $rooms,
        protected RoomAccessService $access,
        protected RoomPresenceService $presence,
        protected RoomMessageService $messages,
        protected RoomTimelineService $timeline,
    ) {}

    /**
     * Resolve everything an endpoint needs, or the failure response to return.
     *
     * @return array{user:User, subject:RoomSubjectInterface, role:RoomRole}|JsonResponse
     */
    protected function context(ClassRoom $room): array|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Your session has expired.', 401);
        }

        $subject = $this->rooms->resolveSubject($room);
        if (! $subject) {
            return $this->notFound('The class this room belongs to no longer exists.');
        }

        // Advance the room's lifecycle before anyone reads it, so the lobby opens
        // and the room closes on time without a scheduled job.
        $room = $this->rooms->syncState($room, $subject);

        $role = $this->access->resolveRole($room, $user, $subject);
        if (! $role) {
            return $this->forbidden('You are not a member of this class.');
        }

        return ['user' => $user, 'subject' => $subject, 'role' => $role];
    }

    /** Panel endpoints that only hosts may read. */
    protected function requireControl(RoomRole $role, string $message): ?JsonResponse
    {
        return $role->canControlRoom() ? null : $this->forbidden($message);
    }
}
