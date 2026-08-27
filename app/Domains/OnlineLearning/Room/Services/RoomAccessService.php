<?php

namespace App\Domains\OnlineLearning\Room\Services;

use App\Domains\OnlineLearning\Room\Contracts\RoomSubjectInterface;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Models\User;

/**
 * Who may see a room, who may enter it, and in what role.
 *
 * Every decision is made here from the session user and the subject — never
 * from anything the client sends. The front end uses the returned capabilities
 * to decide what to draw, not what is permitted.
 */
class RoomAccessService
{
    /**
     * The role this user holds in this room.
     *
     * Host is whoever the subject says hosts it; admins moderate any room;
     * anyone the subject considers enrolled participates; everyone else is out.
     */
    public function resolveRole(ClassRoom $room, User $user, RoomSubjectInterface $subject): ?RoomRole
    {
        if ($subject->hostUserId() === $user->id) {
            return RoomRole::Host;
        }

        if ($user->isAdministrator()) {
            return RoomRole::Moderator;
        }

        if ($subject->isEnrolled($user->id)) {
            return RoomRole::Participant;
        }

        return null;
    }

    public function canView(ClassRoom $room, User $user, RoomSubjectInterface $subject): bool
    {
        return $this->resolveRole($room, $user, $subject) !== null;
    }

    /**
     * Whether this user can enter the room right now, and why not if they cannot.
     *
     * @return array{allowed:bool, reason:?string, role:?RoomRole}
     */
    public function canEnter(ClassRoom $room, User $user, RoomSubjectInterface $subject): array
    {
        $role = $this->resolveRole($room, $user, $subject);

        if ($role === null) {
            return ['allowed' => false, 'reason' => 'You are not a member of this class.', 'role' => null];
        }

        if ($subject->isCancelled() || $room->state->value === 'cancelled') {
            return ['allowed' => false, 'reason' => 'This class has been cancelled.', 'role' => $role];
        }

        // Hosts and moderators may open the room early to set up.
        if ($role->canControlRoom()) {
            return ['allowed' => true, 'reason' => null, 'role' => $role];
        }

        if ($room->isFinished()) {
            return ['allowed' => false, 'reason' => 'This class has ended.', 'role' => $role];
        }

        if (! $room->isEnterable()) {
            $opensAt = $subject->startsAt()->copy()
                ->subMinutes((int) $room->setting('lobby_opens_minutes_before', 15));

            return [
                'allowed' => false,
                'reason' => 'The room opens at ' . $opensAt->format('g:i A') . '.',
                'role' => $role,
            ];
        }

        if ($room->isLive() && ! $room->setting('allow_late_join', true)) {
            return ['allowed' => false, 'reason' => 'Late joining is disabled for this class.', 'role' => $role];
        }

        return ['allowed' => true, 'reason' => null, 'role' => $role];
    }

    /**
     * What the UI is allowed to offer this user. Presentation only — each
     * endpoint re-checks for itself.
     */
    public function capabilities(ClassRoom $room, RoomRole $role): array
    {
        $canControl = $role->canControlRoom();

        return [
            'role' => $role->value,
            'role_label' => $role->label(),
            'can_control_room' => $canControl,
            'can_start' => $canControl,
            'can_end' => $canControl,
            'can_post_message' => (bool) $room->setting('chat_enabled', true),
            'can_post_announcement' => $canControl && (bool) $room->setting('announcements_enabled', true),
            'can_moderate_chat' => $canControl,
            'can_mark_attendance' => $canControl && (bool) $room->setting('attendance_tracking', true),
            'can_view_roster' => $canControl,
            'can_manage_settings' => $canControl,
            'can_view_materials' => (bool) $room->setting('materials_visible', true) || $canControl,
            'can_view_recordings' => (bool) $room->setting('recordings_visible', true) || $canControl,
            'can_view_assignments' => (bool) $room->setting('assignments_visible', true) || $canControl,
        ];
    }
}
