<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_room_participants', function (Blueprint $table) {
            $table->string('livekit_identity')->nullable()->after('display_name')
                ->comment('LiveKit participant identity');
            $table->string('livekit_participant_sid')->nullable()->after('livekit_identity')
                ->comment('LiveKit participant SID');
            $table->boolean('is_hand_raised')->default(false)->after('total_seconds');
            $table->boolean('is_camera_enabled')->default(false)->after('is_hand_raised');
            $table->boolean('is_microphone_enabled')->default(false)->after('is_camera_enabled');
            $table->boolean('is_screen_sharing')->default(false)->after('is_microphone_enabled');
            $table->json('media_state')->nullable()->after('is_screen_sharing')
                ->comment('Detailed media state from LiveKit');

            $table->index('livekit_identity');
            $table->index('is_hand_raised');
        });
    }

    public function down(): void
    {
        Schema::table('class_room_participants', function (Blueprint $table) {
            $table->dropIndex(['livekit_identity', 'is_hand_raised']);
            $table->dropColumn([
                'livekit_identity', 'livekit_participant_sid', 'is_hand_raised',
                'is_camera_enabled', 'is_microphone_enabled', 'is_screen_sharing', 'media_state',
            ]);
        });
    }
};
