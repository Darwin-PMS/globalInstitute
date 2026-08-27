<?php

namespace App\Domains\OnlineLearning\Room\Enums;

/** Entries in a room's activity timeline. */
enum RoomEventType: string
{
    case RoomOpened = 'room_opened';
    case RoomStarted = 'room_started';
    case RoomEnded = 'room_ended';
    case RoomCancelled = 'room_cancelled';
    case ParticipantJoined = 'participant_joined';
    case ParticipantLeft = 'participant_left';
    case AnnouncementPosted = 'announcement_posted';
    case AttendanceMarked = 'attendance_marked';
    case MaterialShared = 'material_shared';
    case RecordingPublished = 'recording_published';
    case SettingsChanged = 'settings_changed';

    public function label(): string
    {
        return match ($this) {
            self::RoomOpened => 'Room opened',
            self::RoomStarted => 'Class started',
            self::RoomEnded => 'Class ended',
            self::RoomCancelled => 'Class cancelled',
            self::ParticipantJoined => 'Joined the room',
            self::ParticipantLeft => 'Left the room',
            self::AnnouncementPosted => 'Announcement posted',
            self::AttendanceMarked => 'Attendance marked',
            self::MaterialShared => 'Material shared',
            self::RecordingPublished => 'Recording published',
            self::SettingsChanged => 'Room settings changed',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::RoomOpened => 'meeting_room',
            self::RoomStarted => 'play_circle',
            self::RoomEnded => 'stop_circle',
            self::RoomCancelled => 'cancel',
            self::ParticipantJoined => 'login',
            self::ParticipantLeft => 'logout',
            self::AnnouncementPosted => 'campaign',
            self::AttendanceMarked => 'how_to_reg',
            self::MaterialShared => 'folder_open',
            self::RecordingPublished => 'smart_display',
            self::SettingsChanged => 'tune',
        };
    }
}
