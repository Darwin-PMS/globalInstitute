<?php
namespace App\Enums;

enum MeetingProvider: string
{
    case GoogleMeet = 'google_meet';
    case Zoom = 'zoom';
    case MicrosoftTeams = 'teams';
    case ExternalLink = 'other';

    public function label(): string
    {
        return match($this) {
            self::GoogleMeet => 'Google Meet',
            self::Zoom => 'Zoom',
            self::MicrosoftTeams => 'Microsoft Teams',
            self::ExternalLink => 'External Link',
        };
    }
}