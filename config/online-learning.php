<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Timezone
    |--------------------------------------------------------------------------
    | Timezone used for all date/time operations within the Online Learning module.
    | Override per-tenant or per-user as needed.
    */
    'timezone' => env('LEARNING_TIMEZONE', 'Asia/Kolkata'),

    /*
    |--------------------------------------------------------------------------
    | Tenant / Context
    |--------------------------------------------------------------------------
    | When true, all major entities will carry a tenant_id column.
    | Useful for multi-tenant SaaS extraction in the future.
    */
    'multi_tenant' => env('LEARNING_MULTI_TENANT', false),

    /*
    |--------------------------------------------------------------------------
    | Class Join Window
    |--------------------------------------------------------------------------
    | How many minutes before class start students can join.
    | How many minutes after class end students can still join.
    */
    'join_window' => [
        'before_start_minutes' => 15,
        'after_end_minutes' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Status Auto-Transition
    |--------------------------------------------------------------------------
    | Automatically transition class status based on time.
    */
    'auto_transition' => [
        'enabled' => true,
        'starting_soon_minutes' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminder Timings
    |--------------------------------------------------------------------------
    | Minutes before class to send reminders.
    | Each entry creates a separate reminder notification.
    */
    'reminders' => [
        'enabled' => true,
        'minutes_before' => [1440, 60, 15], // 24h, 1h, 15min
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance Rules
    |--------------------------------------------------------------------------
    | Thresholds for automatic attendance classification.
    | Values are percentages of total class duration.
    */
    'attendance' => [
        'present_threshold' => 75,    // >= 75% = Present
        'late_threshold' => 10,       // joined after 10% of class = Late
        'partial_threshold' => 25,    // < 75% but >= 25% = Partial
        'absent_threshold' => 0,      // < 25% = Absent
        'auto_mark' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recording Policy
    |--------------------------------------------------------------------------
    */
    'recording' => [
        'auto_publish' => false,
        'default_visibility' => 'enrolled',
        'processing_timeout_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Assignment Policy
    |--------------------------------------------------------------------------
    */
    'assignment' => [
        'allow_late_submission' => true,
        'late_submission_deduct_percent' => 10,
        'max_file_size_mb' => 10,
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'jpg', 'jpeg', 'png'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Quiz Policy
    |--------------------------------------------------------------------------
    */
    'quiz' => [
        'default_time_limit_minutes' => 30,
        'max_time_limit_minutes' => 180,
        'show_correct_answer_after_submission' => true,
        'shuffle_options' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => true,
        'queue' => true,
        'email_enabled' => false,
        'push_enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Meeting Providers
    |--------------------------------------------------------------------------
    | Register available meeting providers.
    | Key maps to the MeetingProvider enum value.
    */
    'meeting_providers' => [
        'google_meet' => [
            'name' => 'Google Meet',
            'enabled' => false,
            'driver' => App\Domains\OnlineLearning\Contracts\MeetingProviderInterface::class,
        ],
        'zoom' => [
            'name' => 'Zoom',
            'enabled' => false,
            'driver' => App\Domains\OnlineLearning\Contracts\MeetingProviderInterface::class,
        ],
        'teams' => [
            'name' => 'Microsoft Teams',
            'enabled' => false,
            'driver' => App\Domains\OnlineLearning\Contracts\MeetingProviderInterface::class,
        ],
        'other' => [
            'name' => 'External Link',
            'enabled' => true,
            'driver' => App\Domains\OnlineLearning\Providers\ExternalLinkMeetingProvider::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    | Which filesystem disk to use for learning materials, assignments, etc.
    */
    'storage' => [
        'disk' => env('LEARNING_STORAGE_DISK', 'public'),
        'materials_directory' => 'learning/materials',
        'assignments_directory' => 'learning/assignments',
        'submissions_directory' => 'learning/submissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Course Provider
    |--------------------------------------------------------------------------
    | Identifies which system provides course data.
    | In the institute project: 'institute'
    | In a standalone project: 'standalone'
    */
    'course_provider' => env('LEARNING_COURSE_PROVIDER', 'institute'),

    /*
    |--------------------------------------------------------------------------
    | Live Class Room
    |--------------------------------------------------------------------------
    | Each scheduled class gets its own independent virtual classroom. These are
    | the defaults a new room is provisioned with; every room then stores its own
    | copy, so changing a default never rewrites a class already in progress.
    */
    'room' => [
        'default_settings' => [
            'chat_enabled' => true,
            'announcements_enabled' => true,
            'materials_visible' => true,
            'recordings_visible' => true,
            'assignments_visible' => true,
            'attendance_tracking' => true,
            'allow_late_join' => true,
            'lobby_opens_minutes_before' => 15,
        ],

        // How long after the scheduled end a room closes itself.
        'auto_close_grace_minutes' => 30,

        // A participant not seen for this long is treated as having left.
        'presence_stale_seconds' => 90,

        /*
         | Client polling intervals, in milliseconds. These are the transport
         | today; a WebSocket layer can replace them without touching the panels,
         | because both feed the same delta endpoints.
         */
        'poll_intervals' => [
            'state' => 15000,
            'presence' => 20000,
            'messages' => 5000,
            'timeline' => 30000,
        ],

        // How many rows a delta request may return in one go.
        'feed_page_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    | Configurable branding for the Online Learning UI.
    */
    'branding' => [
        'institute_name' => env('INSTITUTE_NAME', 'Global Institute & CAD CAM'),
        'logo' => env('INSTITUTE_LOGO', '/images/logo.png'),
        'favicon' => env('INSTITUTE_FAVICON', '/favicon.ico'),
        'primary_color' => env('INSTITUTE_PRIMARY_COLOR', '#1a56db'),
        'secondary_color' => env('INSTITUTE_SECONDARY_COLOR', '#7c3aed'),
        'support_email' => env('INSTITUTE_SUPPORT_EMAIL', 'support@example.com'),
        'support_phone' => env('INSTITUTE_SUPPORT_PHONE', ''),
    ],
];
