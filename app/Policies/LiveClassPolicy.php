<?php

namespace App\Policies;

use App\Domains\OnlineLearning\Room\Models\ClassRoom;
use App\Domains\OnlineLearning\Room\Enums\RoomRole;
use App\Models\User;

class LiveClassPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClassRoom $room): bool
    {
        // Admin can always view
        if ($user->isAdministrator()) {
            return true;
        }

        // Host can always view their own room
        if ($room->host_user_id === $user->id) {
            return true;
        }

        // Participants who are in the room can view
        return $room->participants()->where('user_id', $user->id)->exists();
    }

    public function join(User $user, ClassRoom $room): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        // Check room is enterable
        if (!$room->state->isEnterable()) {
            return false;
        }

        // Check if locked and not already in
        if ($room->setting('is_locked', false) && !$room->participants()->where('user_id', $user->id)->exists()) {
            return false;
        }

        return true;
    }

    public function start(User $user, ClassRoom $room): bool
    {
        return $user->isAdministrator() || $room->host_user_id === $user->id;
    }

    public function end(User $user, ClassRoom $room): bool
    {
        return $user->isAdministrator() || $room->host_user_id === $user->id;
    }

    public function manageParticipants(User $user, ClassRoom $room): bool
    {
        return $user->isAdministrator() || $room->host_user_id === $user->id;
    }

    public function record(User $user, ClassRoom $room): bool
    {
        return $user->isAdministrator() || $room->host_user_id === $user->id;
    }

    public function muteParticipant(User $user, ClassRoom $room): bool
    {
        return $user->isAdministrator() || $room->host_user_id === $user->id;
    }

    public function updateSettings(User $user, ClassRoom $room): bool
    {
        return $user->isAdministrator() || $room->host_user_id === $user->id;
    }

    public function viewRecording(User $user, ClassRoom $room): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        if ($room->host_user_id === $user->id) {
            return true;
        }

        return $room->participants()->where('user_id', $user->id)->exists();
    }

    public function sendMessage(User $user, ClassRoom $room): bool
    {
        if (!$room->setting('chat_enabled', true)) {
            return $user->isAdministrator() || $room->host_user_id === $user->id;
        }

        return true;
    }

    public function raiseHand(User $user, ClassRoom $room): bool
    {
        return $room->state === \App\Domains\OnlineLearning\Room\Enums\RoomState::Live;
    }
}
