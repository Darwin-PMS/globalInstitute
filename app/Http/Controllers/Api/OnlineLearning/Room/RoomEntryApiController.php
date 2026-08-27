<?php

namespace App\Http\Controllers\Api\OnlineLearning\Room;

use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectResolverInterface;
use App\Domains\OnlineLearning\Room\Services\ClassRoomService;
use App\Domains\OnlineLearning\Room\Services\RoomAccessService;
use App\Http\Controllers\Api\OnlineLearning\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\OnlineClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * The door into a room.
 *
 * Callers hold a class id (from a list or calendar) and need the room. This
 * provisions the room on first use and hands back its opaque key, so the rest of
 * the API never needs to know how rooms map to classes.
 */
class RoomEntryApiController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ClassRoomService $rooms,
        protected RoomAccessService $access,
        protected RoomSubjectResolverInterface $resolver,
    ) {}

    /**
     * GET /api/online-learning/classes/{onlineClass}/room
     *
     * Returns the room key for a scheduled class, creating the room if this is
     * the first time anyone has opened it.
     */
    public function resolve(OnlineClass $onlineClass): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Your session has expired.', 401);
        }

        $subject = $this->resolver->wrap($onlineClass);
        if (! $subject) {
            return $this->notFound('This class cannot host a room.');
        }

        $room = $this->rooms->roomFor($subject);

        // Authorise before disclosing the key — it is the room's address.
        if (! $this->access->canView($room, $user, $subject)) {
            return $this->forbidden('You are not a member of this class.');
        }

        $room = $this->rooms->syncState($room, $subject);

        return $this->ok([
            'room_key' => $room->room_key,
            'state' => $room->state->value,
            'url' => '/learning/room/' . $room->room_key,
        ]);
    }
}
