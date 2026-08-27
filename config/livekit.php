<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LiveKit Server URL
    |--------------------------------------------------------------------------
    | The WebSocket URL participants connect to for media.
    */
    'url' => env('LIVEKIT_URL', 'wss://your-cluster.livekit.cloud'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    | Used server-side only. Never exposed to the browser.
    */
    'api_key' => env('LIVEKIT_API_KEY', ''),
    'api_secret' => env('LIVEKIT_API_SECRET', ''),
    'webhook_secret' => env('LIVEKIT_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Token TTL
    |--------------------------------------------------------------------------
    | Access token lifetime in seconds. Default 4 hours.
    */
    'token_ttl' => (int) env('LIVEKIT_TOKEN_TTL', 14400),

    /*
    |--------------------------------------------------------------------------
    | Room Naming Convention
    |--------------------------------------------------------------------------
    | Prefix used for LiveKit room names.
    */
    'room_prefix' => 'class',

    /*
    |--------------------------------------------------------------------------
    | Recording
    |--------------------------------------------------------------------------
    */
    'recording' => [
        'enabled' => env('LIVEKIT_RECORDING_ENABLED', true),
        'storage_disk' => env('LIVEKIT_RECORDING_STORAGE', 'public'),
        'recording_directory' => 'live-class-recordings',
        'layout' => 'speaker',  // speaker, grid, speaker-lightbox
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Room Settings
    |--------------------------------------------------------------------------
    | Applied when creating a new LiveKit room.
    */
    'room_defaults' => [
        'empty_timeout' => 300,           // 5 minutes before empty room closes
        'max_participants' => 200,
        'metadata_ttl' => 86400,          // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Participant Permissions
    |--------------------------------------------------------------------------
    | Default permissions for different roles.
    */
    'permissions' => [
        'instructor' => [
            'can_publish' => true,
            'can_subscribe' => true,
            'can_publish_data' => true,
            'can_publish_sources' => ['camera', 'microphone', 'screen_share', 'screen_share_audio'],
            'can_update_own_metadata' => true,
            'hidden' => false,
        ],
        'student' => [
            'can_publish' => false,
            'can_subscribe' => true,
            'can_publish_data' => true,
            'can_publish_sources' => [],  // empty = restricted by default
            'can_update_own_metadata' => true,
            'hidden' => false,
        ],
        'moderator' => [
            'can_publish' => true,
            'can_subscribe' => true,
            'can_publish_data' => true,
            'can_publish_sources' => ['camera', 'microphone', 'screen_share', 'screen_share_audio'],
            'can_update_own_metadata' => true,
            'hidden' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Events
    |--------------------------------------------------------------------------
    | Which LiveKit events to process.
    */
    'webhook_events' => [
        'room_started',
        'room_finished',
        'participant_joined',
        'participant_left',
        'track_published',
        'track_unpublished',
        'egress_started',
        'egress_updated',
        'egress_ended',
    ],
];
